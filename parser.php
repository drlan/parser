<?php

class Parser {

    # download photo
    function getImage(SimpleXMLElement $items, int $sku): array
    {
        $list = [];

        # check array type
        if (!is_array($items->PHOTO)){
            $photos = ['PHOTO' => $items->PHOTO]; # add element in array

        }
        else {
            $photos = $items->PHOTO;
        }

        $nPhoto = 1;

        #download photo
        foreach ($items as $item) {
            $url = $item->__toString();
            $path = "files/" . $sku;
            if (!file_exists($path)){   #check exist directory
                mkdir($path);
            }
            $pic_name = $path . '/' . $sku . '_' . $nPhoto. '.jpg';

            if (!file_exists($pic_name)){   #check exist picture
                $nPhoto++;
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_HEADER, 0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
                $content = curl_exec($curl);
                curl_close($curl);
                $fp = fopen($pic_name, 'w');
                fwrite($fp, $content);
                fclose($fp);
            }
            else {
                echo "this file exist\n";
            }
            $list[] = [
                'picture' => $pic_name,
            ];
        }
        return $list;
    }

    public function parse($filename){
        $xml = simplexml_load_file($filename);
        $list = [['sku','categories','enabled','family', 'brand', 'characteristics_text','description-ru_RU-ecommerce','name','pn_clean','pn_draft','picture','picture_second','picture_3','picture_4','picture_5','picture_6','picture_7']];

        $sku = 1;

        foreach ($xml->ITEMS->ITEM as $item) {

            $categories = "test";
            $enabled = "0";
            $family = "tovar_no_attr";
            $brand = $item->PROPS->TORGOVAYA_MARKA->__toString();
            $description = $item->DESCRIPTION->__toString();
            $name = $item->NAME->__toString();
            $pn_draft = $item->ARTICLE->__toString();
            $pn_clean = preg_replace('/[^\p{L}0-9\+]/iu','',$pn_draft);

            # for characteristics_text
            $material = $item->PROPS->MATERIAL->__toString();
            $color = $item->PROPS->TSVET->__toString();
            $weight = $item->WEIGHT->__toString();
            $vid_tr = $item->PROPS->VID_TRANSPORTA->__toString();
            $rasp_rul = $item->PROPS->RASPOLOZHENIE_RULYA->__toString();
            $listCharacter = array();
            $key = array('Материал','Цвет','Вес','Вид транспорта','Расположение руля');
            $value = array($material, $color, $weight, $vid_tr, $rasp_rul);
            for ($i=0; $i<count($key); $i++) {
                $listCharacter[$key[$i]] = $value[$i];
            }

            $character = "";
            foreach ($listCharacter as $key=>$value) {
                if (!empty($value)) {
                    $string = "{$key}:{$value}";
                    $character .= $string . "\\\\";
                }
            }

            $picture = $pictureSecond = $picture3 = $picture4 = $picture5 = $picture6 = $picture7 = "";

            foreach ($item->PHOTOS as $photo) {
                $listPhotos = $this->getImage($photo, $sku);
                if (isset($listPhotos[0]['picture'])){$picture = $listPhotos[0]['picture'];}
                if (isset($listPhotos[1]['picture'])){$pictureSecond = $listPhotos[1]['picture'];}
                if (isset($listPhotos[2]['picture'])){$picture3 = $listPhotos[2]['picture'];}
                if (isset($listPhotos[3]['picture'])){$picture4 = $listPhotos[3]['picture'];}
                if (isset($listPhotos[4]['picture'])){$picture5 = $listPhotos[4]['picture'];}
                if (isset($listPhotos[5]['picture'])){$picture6 = $listPhotos[5]['picture'];}
                if (isset($listPhotos[6]['picture'])){$picture7 = $listPhotos[6]['picture'];}
            }

            $list[] = array(
                'sku' => $sku,
                'categories' => $categories,
                'enabled' => $enabled,
                'family' => $family,
                'brand' => $brand,
                'characteristic' => $character,
                'description' => $description,
                'name' => $name,
                'pn_clean' => $pn_clean,
                'pn_draft' => $pn_draft,
                'picture' => $picture,
                'picture_second' => $pictureSecond,
                'picture_3' => $picture3,
                'picture_4' => $picture4,
                'picture_5' => $picture5,
                'picture_6' => $picture6,
                'picture_7' => $picture7,
            );

            $sku++;
            /*if ($sku>100) {
                break;
            }*/
        }

        $csv = fopen('autofamily.csv','w');

        foreach ($list as $field) {
            fputcsv($csv, $field, $delimiter=",");
        }

        fclose($csv);
    }
}

$parser = new Parser();
$parser->parse("catalog_export.xml");

