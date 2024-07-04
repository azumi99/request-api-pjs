<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Google_Client;
use App\Models\FcmTokenModel;
use Illuminate\Support\Facades\Validator;

class FirebaseController extends Controller
{
    private $messaging;
    protected $modelFCM;

    public function __construct()
    {
        $serviceAccountFilePath = base_path('service-account-file.json');

        $firebase = (new Factory)
            ->withServiceAccount($serviceAccountFilePath);
        $this->messaging = $firebase->createMessaging();

        $this->modelFCM = new FcmTokenModel();
    }

    public function SaveToken(Request $request) 
    {
         $validator = Validator::make($request->all(), [
            'token' => 'string|min:1',
            'id_user' => 'integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }
        $token = $request->token;
        $id_user = $request->id_user;
        $valid = $this->modelFCM->where('token', $token)->first();

        if ($valid) {
            $valid->token = $token;
            $valid->id_user = $id_user;
            $valid->update();

            return response()->json(['status' => true, 'message' => 'Token update successfully']);
        }
      
        $userToken = $this->modelFCM->where('id_user', $id_user)->whereNull('token')->first();

        if (!$userToken) {
            $model = $this->modelFCM;
            $model->token = $token;
            $model->id_user = $id_user;
            $model->save();

            return response()->json(['status' => true, 'message' => 'Token saved successfully']);
        }

        return response()->json(['status' => false, 'message' => 'FCM token field is not empty or user token not found']);
    }

    public function sendMessage(Request $request)
    {
        try {
            $accessToken = $this->getAccessToken();
            $message = $this->buildMessage($request->all());

            $response = $this->sendToFcm($accessToken, $message);

            return response()->json([
                'status' => true,
                'message' => 'Message sent successfully',
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error sending message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getAccessToken()
    {
        $client = new Google_Client();
        $client->setAuthConfig(base_path('service-account-file.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();
        return $token['access_token'];
    }

    private function buildMessage($data)
    {
        return [
            'message' => [
                'token' => $data['token'],
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['body'],
                ],
                'data' =>  [
                    'message' => $data['data']['message'],
                    'param' => $data['data']['param'],
                    '_id' => (string) $data['data']['_id'],
                    'user' => $data['data']['user'],
                    'id_chat' => (string) $data['data']['id_chat'],
                ],
                'android' => [
                    'notification' => [
                        'click_action' => 'TOP_STORY_ACTIVITY',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'category' => 'NEW_MESSAGE_CATEGORY',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function sendToFcm($accessToken, $message)
    {
        $url = 'https://fcm.googleapis.com/v1/projects/' . env('FIREBASE_PROJECT_ID') . '/messages:send';

        $response = \Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $message);

        return $response->json();
    }
}