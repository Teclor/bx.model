<?php

namespace Bx\Model\Tests;

use PHPUnit\Framework\TestCase;
use Bx\Model\ModelCollection;
use Bx\Model\FetcherModel;
use Bx\Model\AbsOptimizedModel;
use Bx\Model\Tests\Samples\EmptyModelService;

class FetcherModelPerformanceTest extends TestCase
{
    private const COLLECTION_SIZE = 1000;
    private const MULTIPLE_VALUE_MULTIPLIER = 3;

    public function testInitAsSingleValuePerformance()
    {
        $collection = $this->generateSingleFKCollection();
        $linkedCollection = $this->generateLinkedCollection(self::COLLECTION_SIZE);

        $linkedService = new EmptyModelService();
        $linkedService->resultList = $linkedCollection;

        $fetcherModel = FetcherModel::initAsSingleValue(
            $linkedService,
            'externalModel',
            'model_id',
            'id'
        );

        $startTime = microtime(true);
        $fetcherModel->fill($collection);
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;
        $this->assertTrue(true);
        echo "\nВремя выполнения initAsSingleValue: " . number_format($executionTime, 4) . " секунд\n";
    }

    public function testInitAsMultipleValuePerformance()
    {
        $collection = $this->generateMultipleFKCollection();
        $linkedDataSize = self::COLLECTION_SIZE * self::MULTIPLE_VALUE_MULTIPLIER;
        $linkedCollection = $this->generateLinkedCollection($linkedDataSize);

        $linkedService = new EmptyModelService();
        $linkedService->resultList = $linkedCollection;

        $fetcherModel = FetcherModel::initAsMultipleValue(
            $linkedService,
            'externalModel',
            'model_id',
            'id'
        );

        $startTime = microtime(true);
        $fetcherModel->fill($collection);
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;
        $this->assertTrue(true);
        echo "\nВремя выполнения initAsMultipleValue: " . number_format($executionTime, 4) . " секунд\n";
    }

    /**
     * @return ModelCollection
     */
    private function generateSingleFKCollection(): ModelCollection
    {
        $size = self::COLLECTION_SIZE;
        $data = [];
        $modelIds = range(1, $size);
        shuffle($modelIds);

        for ($i = 0; $i < $size; $i++) {
            $data[] = [
                'id' => $i + 1,
                'model_id' => array_pop($modelIds),
            ];
        }
        return $this->initCollection($data);
    }

    /**
     * @return ModelCollection
     */
    private function generateMultipleFKCollection(): ModelCollection
    {
        $size = self::COLLECTION_SIZE;
        $linkedDataSize = self::COLLECTION_SIZE * self::MULTIPLE_VALUE_MULTIPLIER;
        $data = [];
        $modelIds = range(1, $linkedDataSize);
        shuffle($modelIds);
        $modelChunks = array_chunk($modelIds, self::MULTIPLE_VALUE_MULTIPLIER);

        for ($i = 0; $i < $size; $i++) {
            $data[] = [
                'id' => $i + 1,
                'model_id' => array_pop($modelChunks),
            ];
        }
        return $this->initCollection($data);
    }

    /**
     * @param int $size Размер коллекции
     * @return ModelCollection
     */
    private function generateLinkedCollection(int $size): ModelCollection
    {
        $data = [];
        for ($i = 0; $i < $size; $i++) {
            $data[] = [
                'id' => $i + 1,
                'title' => "model " . ($i + 1),
            ];
        }
        return $this->initCollection($data);
    }

    /**
     * @param array $data
     * @return ModelCollection
     */
    private function initCollection(array $data): ModelCollection
    {
        $collection = new ModelCollection([], AbsOptimizedModel::class);
        foreach ($data as $item) {
            $collection->append($this->initModel($item));
        }
        return $collection;
    }

    /**
     * @param array $data
     * @return AbsOptimizedModel
     */
    private function initModel(array $data): AbsOptimizedModel
    {
        return new class($data) extends AbsOptimizedModel {
            protected function toArray(): array
            {
                return $this->data;
            }
        };
    }
}