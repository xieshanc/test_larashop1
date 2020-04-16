<?php

namespace App\Console\Commands\Elasticsearch;

use Illuminate\Console\Command;

class Migrate extends Command
{
    protected $signature = 'es:migrate';
    protected $description = 'Elasticsearch 索引结构迁移';
    protected $es;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->es = app('es');
        $indices = [
            Indices\ProjectIndex::class,
        ];

        foreach ($indices as $indexClass) {
            $aliasName = $indexClass::getAliasName();
            $this->info('正在处理索引: ' . $aliasName);

            if (!$this->es->indices()->exists(['index' => $aliasName])) {
                $this->info('索引不存在，准备创建');
                $this->createIndex($aliasName, $indexClass);
                $this->info('创建成功，准备初始化数据');
                $indexClass::rebuild($aliasName);
                $this->info('操作成功');
                continue;
            }

            try {
                $this->info('索引存在，准备更新');
                $this->updateIndex($aliasName, $indexClass);
            } catch (\Exception $e) {
                $this->warn('更新失败，准备重建');
                $this->reCreateIndex($aliasName, $indexClass);
            }
            $this->info($aliasName . '操作成功');
        }
    }

    protected function createIndex($aliasName, $indexClass)
    {
        // echo '<pre>';
        // var_dump($aliasName, $indexClass::getAliasName());
        // exit;
        $this->es->indices()->create([
            'index' => $aliasName . '_0',
            'body'  => [
                'settings' => $indexClass::getSettings(),
                'mappings' => [
                    'properties' => $indexClass::getProperties(),
                ],
                'aliases'  => [
                    $aliasName => new \stdClass(),
                ],
            ],
        ]);
    }

    protected function updateIndex($aliasName, $indexClass)
    {
        // echo '<pre>';
        // var_dump($aliasName, $indexClass::getAliasName());
        // exit;
        $this->es->indices()->close(['index' => $aliasName]);
        $this->es->indices()->putSettings([
            'index' => $aliasName,
            'body'  => $indexClass::getSettings(),
        ]);
        $this->es->indices()->putMapping([
            'index' => $aliasName,
            'body'  => [
                'properties' => $indexClass::getProperties(),
            ],
        ]);
        $this->es->indices()->open(['index' => $aliasName]);
    }

    protected function reCreateIndex($aliasName, $indexClass)
    {
        // echo '<pre>';
        // var_dump($aliasName, $indexClass::getAliasName());
        // exit;
        $indexInfo      = $this->es->indices()->getAliases(['index' => $aliasName]);
        $indexName = array_keys($indexInfo)[0];
        if (!preg_match('/_(\d+)$/', $indexName, $m)) {
            $msg = '索引名称不正确:' . $indexName;
            $this->error($msg);
            throw new \Exception($msg);
        }
        $newIndexName = $aliasName . '_' . ($m[1] + 1);
        $this->info('正在创建索引' . $newIndexName);
        $this->es->indices()->create([
            'index' => $newIndexName,
            'body'  => [
                'settings'  => $indexClass::getSettings(),
                'mappings'  => [
                    'properties'    => $indexClass::getProperties(),
                ],
            ],
        ]);
        $this->info('创建成功，准备重建数据');
        $indexClass::rebuild($newIndexName);
        $this->info('重建成功，准备修改别名');
        $this->es->indices()->putAlias(['index' => $newIndexName, 'name' => $aliasName]);
        $this->info('修改成功，准备删除旧索引');
        $this->es->indices()->delete(['index' => $indexName]);
        $this->info('删除成功');
    }
}
