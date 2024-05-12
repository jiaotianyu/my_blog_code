<?php
namespace elasticsearch;

use Elasticsearch\ClientBuilder;

class Elastic {

    protected $esClient;
    protected $params;
    // 公共方法连接ES，减少代码冗余
    public function __construct($index)
    {
        $host = [env('elastic.host', 'localhost:9200')];
        $this->esClient = ClientBuilder::create()           // Instantiate a new ClientBuilder
        ->setHosts($host)      // Set the hosts
        ->build();

        $this->params = array(
            'index' => $index,
            'body' => []
        );
    }

    /**
     * 创建文档
     * @param $id       integer 文档ID
     * @param $body     array   数据信息
     * @return array|callable
     */
    public function createDoc($id, $body) {
        $this->params['body'] = $body;
        $this->params['id'] = $id;
        return $this->esClient->index($this->params);
    }

    /**
     * 搜索文档
     * @param $body
     * @return array
     */
    public function searchDoc($body) {
        $this->params['body'] = $body;
        $res = $this->esClient->search($this->params);
        if ($res['_shards']['failed']) {
            return ['code' => SYSTEM_ERROR, 'message' => $res['error']['type']];
        } else {
            $data = array();
            foreach($res['hits']['hits'] as $k => $value) {
                $data[$k]['id'] = $value['_id'];
                $data[$k]['title'] = $value['_source']['title'];
                $data[$k]['content'] = $value['_source']['content'];
            }
            return ['code' => SUCCESS, 'data' => $data];
        }
    }
}