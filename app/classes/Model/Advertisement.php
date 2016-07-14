<?php

namespace Model;

use MongoDate;
use Utils\MongoHelper;
use Mandrill\Mandrill;

class Advertisement
{
    protected $config;
    protected $container;
    protected $collection;

    public function __construct($container)
    {
        $this->container  = $container;
        $this->collection = $container['database']->sAdv;
    }

    public function insert(array $data)
    {
        $doc = [
            'sourceClassifiedID' => trim($data['id']),
            'sourceCategory' => trim($data['categoryId']),
            'sourceTitle' => trim($data['title']),
            'sourcePrice' => (float)trim($data['price']),
            'sourceCurrency' => trim($data['currency']),
            'sourceImages' => $data['images'],
            'createDate' => new MongoDate(),
            'updateDate' => new MongoDate()
        ];

        $this->collection->insert($doc);

        return (string)$doc['_id']; // Last inserted mongo id
    }

    public function validateInsert($data)
    {
        $validator = $this->container->get('validator');

        $validator->setRequestParams($data);

        if (! is_array($data)) {
            $validator->setMessage("Data must be array.");
            return false;
        }

        $validator->setRules('id', 'id', 'required');
        $validator->setRules('categoryId', 'categoryId', 'required');
        $validator->setRules('title', 'title', 'required');
        $validator->setRules('price', 'price', 'required');
        $validator->setRules('currency', 'currency', 'required');

        if ($validator->isValid()) {
            return true;
        }
        return false;
    }

    public function getValidator()
    {
        return $this->container->get('validator');
    }

    public function getAll($limit = 0,$ofset = 0)
    {
        $cursor = $this->collection->find()->sort(['updateDate' => -1])->skip($ofset)->limit($limit);

        if (empty($cursor)) {
            return false;
        }

        $results = array();
        foreach ($cursor as $doc) {
            $results[] = $doc;
        }
        return $results;
    }

    public function getAllCount()
    {
        $result = $this->collection->count();

        return $result;
    }

    public function update($data, $id)

    {
        $validator = $this->container->get('validator');

        if (! is_array($data)) {
            $validator->setMessage("Data must be array.");
            return false;
        }

        $data['updateDate'] = new MongoDate();

        $this->collection->update(
            ['_id' => MongoHelper::id($id)],
            ['$set' => $data]
        );
        return true;
    }

    public function remove($id)
    {
        $row = $this->collection->findOne(['_id' => MongoHelper::id($id)]);
        if (empty($row)) {
            return false;
        }
        $this->collection->remove(['_id' => MongoHelper::id($id)]);
        return true;
    }

    public function getById($id)
    {
        $row = $this->collection->findOne(['_id' => MongoHelper::id($id)]);
        if (empty($row)) {
            return false;
        }

        return $row;
    }

}
