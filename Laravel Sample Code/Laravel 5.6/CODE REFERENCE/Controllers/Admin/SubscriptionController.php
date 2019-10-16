<?php

namespace App\Http\Controllers\Admin;

Use App\Http\Controllers\Controller;
Use App;
Use Illuminate\Http\Request;
Use Validator;
Use Redirect;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Plan;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Currency;
use PayPal\Api\Patch;
use PayPal\Common\PayPalModel;
use PayPal\Api\PatchRequest;
use App\Models\Subscription;

class SubscriptionController extends Controller {

    public function showSubscriptionPlanPage() {
        return View('admin.subscriptions.subscription')->with('page_title', 'Add Subscription');
    }

    public function setPaypalConfig() {
        if (config('paypal.settings.mode') == 'live') {
            $client_id = config('paypal.live_client_id');
            $secret = config('paypal.live_secret');
        } else {
            $client_id = config('paypal.sandbox_client_id');
            $secret = config('paypal.sandbox_secret');
        }

        $apiContext = new ApiContext(new OAuthTokenCredential($client_id, $secret));
        $apiContext->setConfig(config('paypal.settings'));
        return $apiContext;
    }

    public function addSubscriptionPlan(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
                    'plan_name' => 'required|max:128',
                    'plan_desc' => 'required|max:127',
                    'payment_name' => 'required|max:128',
                    'payment_frequency' => 'required|in:WEEK,DAY,YEAR,MONTH',
                    'payment_frequency_interval' => 'required|max:12',
                    'payment_cycle' => 'required|max:12',
                    'amount' => 'required',
                    'auto_bill_amount' => 'required|in:NO,YES',
                    'initial_fail_amount_action' => 'required|in:CONTINUE,CANCEL',
                    'status' => 'required|in:Enabled,Disabled'
        ]);

        if ($validator->fails()) {
            return Redirect::back()
                            ->withErrors($validator)->with('plan', $input);
        } else {
            $apiContext = $this->setPaypalConfig();
            $plan = new Plan();
            $plan->setName($input['plan_name'])
                    ->setDescription($input['plan_desc'])
                    ->setType('FIXED');

            // Set billing plan definitions
            $paymentDefinition = new PaymentDefinition();
            $paymentDefinition->setName($input['payment_name'])
                    ->setType('REGULAR')
                    ->setFrequency($input['payment_frequency'])
                    ->setFrequencyInterval($input['payment_frequency_interval'])
                    ->setCycles($input['payment_cycle'])
                    ->setAmount(new Currency(array('value' => $input['amount'], 'currency' => 'USD')));

            // Set merchant preferences
            $merchantPreferences = new MerchantPreferences();
            $merchantPreferences->setReturnUrl(url("/recurring_success"))
                    ->setCancelUrl(url("/recurring_success"))
                    ->setAutoBillAmount($input['auto_bill_amount'])
                    ->setInitialFailAmountAction($input['initial_fail_amount_action'])
                    ->setMaxFailAttempts('4');

            $plan->setPaymentDefinitions(array($paymentDefinition));
            $plan->setMerchantPreferences($merchantPreferences);

            try {
                $createdPlan = $plan->create($apiContext);

                try {
                    $patch = new Patch();
                    $value = new PayPalModel('{"state":"ACTIVE"}');
                    $patch->setOp('replace')
                            ->setPath('/')
                            ->setValue($value);
                    $patchRequest = new PatchRequest();
                    $patchRequest->addPatch($patch);
                    $createdPlan->update($patchRequest, $apiContext);
                    $plan = Plan::get($createdPlan->getId(), $apiContext);
                    $plan_id = $plan->getId();
                    if(!empty($plan_id)){
                        $input['plan_id'] = $plan_id;
                        $response = Subscription::create($input);
                        if(!empty($response)){
                           return Redirect::to('/admin/subscription_plan_list')->with('message', 'Subscription plan added successfully'); 
                        } else {
                           return Redirect::back()->with('error_message', 'Something went wrong');  
                        }
                    } else {
                        return Redirect::back()->with('error_message', 'Something went wrong');
                    }
                    
                } catch (\PayPal\Exception\PayPalConnectionException $ex) {
//                    echo $ex->getCode();
//                    echo $ex->getData();
//                    die($ex);
                    return Redirect::back()->with('error_message', 'Something went wrong');
                } catch (\Exception $ex) {
                    return Redirect::back()->with('error_message', 'Something went wrong');
                }
            } catch (\PayPal\Exception\PayPalConnectionException $ex) {
                return Redirect::back()->with('error_message', 'Something went wrong');
            } catch (\Exception $ex) {
                return Redirect::back()->with('error_message', 'Something went wrong');
            }

            
        }
    }

    public function showSubscriptionPlanList(Request $request) {
      if($request->has('search'))
        {
            $keyword = $request->input('search');
            $subscriptions = Subscription::orderBy('id', 'desc')
                ->where(function($query) use($keyword) {
                    $query->orWhere('id', 'LIKE', "%$keyword%")
                        ->orWhere('plan_id', 'LIKE', "%$keyword%")
                        ->orWhere('plan_name', 'LIKE', "%$keyword%")
                        ->orWhere('plan_desc', 'LIKE', "%$keyword%")
                        ->orWhere('payment_name', 'LIKE', "%$keyword%")
                        ->orWhere('payment_frequency', 'LIKE', "%$keyword%")
                        ->orWhere('payment_frequency_interval', 'LIKE', "%$keyword%")
                        ->orWhere('payment_cycle', 'LIKE', "%$keyword%")
                        ->orWhere('amount', 'LIKE', "%$keyword%")
                        ->orWhere('auto_bill_amount', 'LIKE', "%$keyword%")
                        ->orWhere('initial_fail_amount_action', 'LIKE', "%$keyword%")
                        ->orWhere('status', 'LIKE', "%$keyword%")        
                        ->orWhere('created_at', 'LIKE', "%$keyword%")
                        ->orWhere('updated_at', 'LIKE', "%$keyword%");
                })
                ->paginate(10);
        }else{
            $subscriptions = Subscription::orderBy('id','DESC')->paginate(10);
        }
        return View('admin.subscriptions.subscriptionlist')->with('page_title','Subscription List')->with('subscriptions',$subscriptions);

    }

}
