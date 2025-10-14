<?php
namespace App\Services\Common;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Throwable;

class DocumentLockService
{
      /**
     * Constructor to inject cache dependency
     */
  

    /**
     * Run a callback safely using a cache-based lock
     * stored in a specific Redis store (e.g., redis_p360).
     *
     * @param string   $lockKey       Unique key for the operation (e.g. "book_123_doc_567")
     * @param callable $callback      Code to execute safely under lock
     * @param int      $lockSeconds   Lock TTL in seconds
     * @param int      $waitSeconds   Max time to wait for acquiring lock
     * @param string   $storeName     Cache store name (default: "redis_p360")
     * @return array                  ['success' => bool, 'message' => string, 'status' => int, 'data' => mixed|null]
     */
    
    function lockDocumentNumber(
        string $lockKey,
        int $lockSeconds=20, 
        callable $callback, 
        string $storeName = 'redis'
        )
    {
 
        $uniqueKey = "erp_p360_lock_{$lockKey}";
        $lock = Cache::store($storeName)->lock($uniqueKey, $lockSeconds);
        try {
            if ($lock->get()) {
                    $result = $callback();

                    return [
                        'success' => true,
                        'message' => 'Operation executed successfully under lock.',
                        'status'  => 200,
                        'data'    => $result,
                    ];
            }

            return [
                'success' => false,
                'message' => 'Another process is currently running. Please try again later.',
                'status'  => 423, 
                'data'    => null,
            ];

        } catch (LockTimeoutException $e) {
            return [
                'success' => false,
                'message' => 'Failed to acquire lock within timeout.',
                'status'  => 408,
                'data'    => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'status'  => 500,
                'data'    => null,
            ];
        }
    }
}
