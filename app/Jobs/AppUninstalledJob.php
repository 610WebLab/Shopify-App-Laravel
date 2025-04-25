<?php namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;
use App\Models\User;
use App\Models\Shippingzone;
use App\Models\Shippingmethod;
use App\Models\Zonemethod;
use App\Models\Flaterate;
use App\Models\Localpickup;
use App\Models\Freeshipping;
use App\Models\Tablerateoption;
use App\Models\Tablerates;
use App\Traits\FreeShippingTrait;
use App\Traits\TableRateTrait;
use App\Traits\LocalPickupTrait;
use App\Traits\FlatRateTrait;
use Log;
use DB;

class AppUninstalledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Shop's myshopify domain
     *
     * @var ShopDomain|string
     */
    public $shopDomain;

    /**
     * The webhook data
     *
     * @var object
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param string   $shopDomain The shop's myshopify domain.
     * @param stdClass $data       The webhook data (JSON decoded).
     *
     * @return void
     */
    public function __construct($shopDomain, $data)
    {
        $this->shopDomain = $shopDomain;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Convert domain
        $this->shopDomain = ShopDomain::fromNative($this->shopDomain);

        try {
            Log::debug("AppUninstalledJob : ".$this->shopDomain->toNative());

            // Start a database transaction
            // DB::beginTransaction();
            $shop = User::where('name', $this->shopDomain->toNative())->first();

            if ($shop) {

                $shop->status = 'false';
                $shop->password = '';
                $shop->save();
       
                $shop->delete();
            } else {
                throw new \Exception("Shop not found");
            }
            // $shopId = $shop->id;
            // User::where('id', $shopId)->delete();
            // $shop1 = User::where('id', $shopId)->first();
        
            
            // $shippingZone = Shippingzone::where('user_id', $shop->id)->get();
            // if($shippingZone->isNotEmpty()) {
            //     foreach($shippingZone as $shipZone){
            //         if (Zonemethod::where('zone_id', $shipZone->id)->count() > 0) {
            //             Zonemethod::where('zone_id', $shipZone->id)->delete();
            //         }
            //     }
            //     Shippingzone::where('user_id', $shop->id)->delete();
            // }
            // Flaterate::where('user_id', $shop->id)->delete();
            // Localpickup::where('user_id', $shop->id)->delete();
            // Freeshipping::where('user_id', $shop->id)->delete();
            // $tableRate = Tablerates::where('user_id', $shop->id)->get();
            // if($tableRate->isNotEmpty())
            // {
            //     foreach($tableRate as $table){
            //         if (Tablerateoption::where('table_rate_id', $table->id)->count() > 0) {
            //             Tablerateoption::where('table_rate_id', $table->id)->delete();
            //         }
            //     }
            //     Tablerates::where('user_id', $shop->id)->delete();
            // }
            
            // // Log::info(print_r($shop, true));
            // $shop->forceDelete();
            // DB::commit();

            Log::info("Deleted records for shop: " . $shop);
        } catch(\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
        }

        // Do what you wish with the data
        // Access domain name as $this->shopDomain->toNative()
    }
}
