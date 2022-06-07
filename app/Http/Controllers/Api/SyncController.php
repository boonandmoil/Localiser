<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Exception;
use SimpleXMLElement;
use ParseCsv\Csv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SyncController extends Controller
{
    public function convert(Request $request){

        $output_type = $request->input('output_type');
        $source_file = $request->csv_file;

        $csv = new Csv();
        $csv->delimiter = ',';
        $csv->parseFile($source_file);

        $headers = collect($csv->titles)->filter(function ($value) {
            return preg_match('/^[a-zA-Z]+$/', $value);
        });

        $data = $csv->data;

        $seperated = [];

        foreach ($data as $i => $row) {

            $string_key = $row['Key'];

            foreach($row as $lang => $string){

                if($lang == "Key" || !$headers->contains($lang)){
                    continue;
                }
                if(trim($string) !== ""){
                    $seperated[$lang][$string_key] = $string;
                }

        
            }

        }

        $this->createXML($seperated);

    } 

    private function createXML($data){

        foreach($data as $lang => $strings){

            $resources = new ComplexXMLElement('<resources></resources>');
            
            ksort($strings, SORT_NATURAL);

            foreach($strings as $key => $string){

                if($string != strip_tags($string)) {

                    $resources->addChildWithCData($key, $this->prepareHTMLStringForXML($string));

                } else {
                    
                    $stringNode = $resources->addChild('string', $this->prepareString($string));
                    $stringNode->addAttribute('name', $key);

                }

            }

            $dom = dom_import_simplexml($resources);

            $exportContents = $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);

            //$hardCodedPath = "/Users/marie/Documents/Projects/BAM/Clients/SmokeFree/smokefree-android-diga/app/src/main/res";

            $lang_prefix = $this->langCodePrefixForLanguage($lang);

            $hardCodedPath = "";

            $file_path = $hardCodedPath . "/values" . $lang_prefix . "/strings.xml";

            Storage::disk('local')->put($file_path, $this->prettifyXML($exportContents));

        }


        echo "ok";

    } 

    private function prepareHTMLStringForXML($string){
        
        $string = str_replace("’", "&rsquo;", $string);
        $string = str_replace("'", "&rsquo;", $string);

        return $string;
    }

    private function prepareString($string){
        $string = str_replace("\n", "\\n", $string);
        return $string;
    }

    private function prettifyXML($string){

        $string = str_replace("</resources>", "\n</resources>", $string);
        $string = str_replace("<string", "\n<string", $string);

        $string = str_replace("\&#13;", "\\n", $string);
        $string = str_replace("&#13;", "\\n", $string);

        $string = str_replace("\\n\n", "\\n", $string);

        $string = str_replace("'", "\'", $string);

        return $string;
    }

    private function langCodePrefixForLanguage($lang){
        switch($lang){
            case "English":
                return ""; // en is default
            case "German":
                return "-de";
            case "French":
                return "-fr";
            case "Spanish":
                return "-es";
            case "Portuguese":
                return "-pt";
            case "Russian":
                return "-ru";
            case "Welsh":
                return "-cy";
        }
    }
}

class ComplexXMLElement extends SimpleXMLElement {
    
    public function addChildWithCData($key, $value = NULL) {
        
        $new_child = $this->addChild("string");
        $new_child->addAttribute('name', $key);

        $node = dom_import_simplexml($new_child); 
        $no = $node->ownerDocument; 
        $node->appendChild($no->createCDATASection($value)); 
    
        return $new_child;
    }
}
