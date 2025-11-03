<?php
namespace App\Services\Common;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use App\Helpers\ConstantHelper;
use Throwable;

class DocumentLockService
{
    protected $storeName;
    protected $lockSeconds;

    public function __construct()
    {
        $this->lockSeconds = ConstantHelper::LOCK_SECOND; //Lock TTL in seconds
        $this->storeName = env('CACHE_DRIVER') === 'redis' ? 'redis' : 'file';
    }


    /**
     * Run a callback safely using a cache-based lock
     * stored in a specific Redis store (e.g., redis).
     *
     * @param string   $lockKey       Unique key for the operation (e.g. "book_123_doc_567")
     * @return array                  ['success' => bool, 'message' => string, 'status' => int, 'data' => mixed|null]
     */


    public function generateLockKey($organizationId, $bookId, $documentNumber)
    {
        if (!$organizationId || !$bookId || !$documentNumber)
            return null;
        return "org_{$organizationId}_book_{$bookId}_doc_{$documentNumber}";
    }

    public function generateItemLockKey($organizationId, $refDetailId, $type)
    {
        if (!$organizationId || !$refDetailId || !$type)
            return null;
        return "module_{$type}_org_{$organizationId}_key_{$refDetailId}";
    }

    public function lockDocumentNumber(
        string $lockKey,
    ) {

        $lock = Cache::store($this->storeName)->lock($lockKey, $this->lockSeconds);
        try {
            if ($lock->get()) {

                return [
                    'success' => true,
                    'message' => 'Operation executed successfully under lock.',
                    'status' => 200,
                ];
            }

            return [
                'success' => false,
                'message' => 'Another process is currently running for the same document number. Please try again later',
                'status' => 423,
                'data' => null,
            ];

        } catch (LockTimeoutException $e) {
            return [
                'success' => false,
                'message' => 'Failed to acquire lock within timeout.',
                'status' => 408,
                'data' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'status' => 500,
                'data' => null,
            ];
        }
    }


    public function lockRemainingItemQty($lockKey, $itemCode, $type)
    {
        $lock = Cache::store($this->storeName)->lock($lockKey, $this->lockSeconds);
        try {
            if ($lock->get()) {

                return [
                    'success' => true,
                    'message' => 'Operation executed successfully under lock.',
                    'status' => 200,
                ];
            }

            return [
                'success' => false,
                'message' => 'Another process is currently running for the same ' . $type . ' item: ' . ($itemCode) . '. Please try again later',
                'status' => 423,
                'data' => null,
            ];

        } catch (LockTimeoutException $e) {
            return [
                'success' => false,
                'message' => 'Failed to acquire lock within timeout.',
                'status' => 408,
                'data' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'status' => 500,
                'data' => null,
            ];
        }
    }
}
