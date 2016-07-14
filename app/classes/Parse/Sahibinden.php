<?php

/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 15.06.2016
 * Time: 13:40
 */

namespace Parse;

use Curl\Curl;
use Model\Advertisement;
use Utils\MongoHelper;

class Sahibinden
{

    protected $sahibindenCurl;
    protected $advertisementManagement;
    protected $container;
    protected $headers = array(
        'User-Agent'       => 'Sahibinden-Android/3.0.1 (191; Android 5.0; asus ASUS_Z00AD)',
        'Content-Type'     => 'application/json; charset=utf-8',
        'x-api-key'        => 'e91092ad5ea2e030c201ce9ac4373f6b565a7842',
        'x-timestamp'      => '1464951819080',
        'x-client-profile' => 'Generic_v1.8',
        'x-api-hash'       => 'D3E019B4826874056F57184E14630B9C3ED51397',
    );

    public function __construct($container)
    {
        $this->container = $container;
        $this->sahibindenCurl = new Curl();

        $this->advertisementManagement = new Advertisement($container);

        $this->sahibindenCurl->setOpt(CURLOPT_TIMEOUT,2000);

        foreach ($this->headers as $key => $header) {
            $this->sahibindenCurl->setHeader($key,$header);
        }
    }

    public function pull()
    {
        $response = [];
        foreach ($this->container["sahibindenCategoryAndType"]["category"] as $category) {

            foreach ($category['sub'] as $subCategory) {


                foreach ($subCategory['sub'] as $item) {

                    $page = 0;
                    do{

                        $advertisements = [];
                        $advertisement = $this->sahibindenCurl->get('https://api.sahibinden.com/sahibinden-ral/rest/classifieds/search?category='.$item["id"].'&address_country=1&sorting=bm&language=tr&pagingOffset='.$page.'&pagingSize=100&pagingSize=100');
                        $advertisement = json_decode(json_encode($advertisement),true);

                        if($advertisement["success"])
                        {
                            $adv = $advertisement["response"]["classifieds"];

                            foreach ($adv as $a) {
                                array_push($advertisements,$a);
                            }

                            $response[] = array(
                                'a' => 'https://api.sahibinden.com/sahibinden-ral/rest/classifieds/search?category='.$item["id"].'&address_country=1&sorting=bm&language=tr&pagingOffset='.$page.'&pagingSize=100&pagingSize=100',
                                'b' => $this->saveAdvertisement($advertisements)
                            );
//                            $response[] = $this->saveAdvertisement($advertisements);
                        }

                        $page+=100;

                        if($page>1000)
                            break;

                    }while($advertisement["success"]);

                    unset($advertisement);

                }

            }

        }

        return $response;
    }

    public function saveAdvertisement($advertisements)
    {
        $advertisementIds = [];
        $advertisementErrorIds = [];

        foreach ($advertisements as $advertisement) 
        {
            $query = $this->advertisementManagement->getByIdAndEmlakSource($advertisement["id"],"sahibinden");
            $temp = $this->sahibindenCurl->get("https://api.sahibinden.com/sahibinden-ral/rest/classifieds/".$advertisement["id"]."?language=tr");
            $adv = $temp->response;

            if ($query)
            {

                $response = true;
                if($query["sourceClassifiedDate"] != $adv->classifiedDate)
                {
                    $response = $this->advertisementManagement->update(array('updateDate'=>new \MongoDate()),$query['_id']);
                }

                if($response)
                    $advertisementIds[] = $advertisement["id"];
                else
                    $advertisementErrorIds[] = $advertisement["id"];
            }
            else
            {
                $adv->emlakSource = "sahibinden";

                $adv->type = $adv->categoryBreadcrumb[count($adv->categoryBreadcrumb)-1]->label;

                $adv->attributes = $adv->sections[0]->attributes;

                $images = [];
                foreach ($adv->images as $image) {
                    $images[] = $image->normal;
                }

                $adv->images = $images;

                if (! $this->advertisementManagement->validateInsert(json_decode(json_encode($adv),true))) {
                    $validator = $this->advertisementManagement->getValidator();
                    $validator->setMessage('Validation error.');
                    return array(
                        'success' => 0, 'messages' => $validator->getMessages(), 'error' => $validator->getErrors()
                    );
                }

                $response = $this->advertisementManagement->insert(json_decode(json_encode($adv),true));

                if($response)
                    $advertisementIds[] = $advertisement["id"];
                else
                    $advertisementErrorIds[] = $advertisement["id"];
            }

        }

        return array(
            'errorAdvIds'   => $advertisementErrorIds,
            'successAdvIds' => $advertisementIds
        );
    }

}