<?php

class OpenAI
{

    public $openAiEndpoint = 'https://api.openai.com/v1';

    private string $engine = "davinci";
    private string $model = "text-davinci-002";
    private string $chatModel = "gpt-3.5-turbo";
    private array $headers;
    private array $contentTypes;
    private int $timeout = 0;
    private object $stream_method;
    private string $customUrl = "";
    private string $proxy = "";
    private array $curlInfo = [];

    public function __construct($OPENAI_API_KEY)
    {
        $this->contentTypes = [
            "application/json"    => "Content-Type: application/json",
            "multipart/form-data" => "Content-Type: multipart/form-data",
        ];

        $this->headers = [
            $this->contentTypes["application/json"],
            "Authorization: Bearer $OPENAI_API_KEY",
        ];
    }

    /**
     * @return array
     * Remove this method from your code before deploying
     */
    public function getCURLInfo()
    {
        return $this->curlInfo;
    }

    /**
     * @return bool|string
     */
    public function listModels()
    {
        $url = $this->fineTuneModel();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $model
     * @return bool|string
     */
    public function retrieveModel($model)
    {
        $model = "/$model";
        $url   = $this->fineTuneModel().$model;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $opts
     * @return bool|string
     * @deprecated
     */
    public function complete($opts)
    {
        $engine = $opts['engine'] ?? $this->engine;
        $url    = $this->completionURL($engine);
        unset($opts['engine']);
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param        $opts
     * @param  null  $stream
     * @return bool|string
     * @throws Exception
     */
    public function completion($opts, $stream = null)
    {
        if ($stream != null && array_key_exists('stream', $opts)) {
            if (!$opts['stream']) {
                throw new Exception(
                    'Please provide a stream function. Check https://github.com/orhanerday/open-ai#stream-example for an example.'
                );
            }

            $this->stream_method = $stream;
        }

        $opts['model'] = $opts['model'] ?? $this->model;
        $url           = $this->chatUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     */
    public function createEdit($opts)
    {
        $url = $this->editsUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     */
    public function image($opts)
    {
        $url = $this->imageUrl()."/generations";
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     */
    public function imageEdit($opts)
    {
        $url = $this->imageUrl()."/edits";
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     */
    public function createImageVariation($opts)
    {
        $url = $this->imageUrl()."/variations";
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @deprecated
     */
    public function search($opts)
    {
        $engine = $opts['engine'] ?? $this->engine;
        $url    = $this->searchURL($engine);
        unset($opts['engine']);
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @deprecated
     */
    public function answer($opts)
    {
        $url = $this->answersUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @deprecated
     */
    public function classification($opts)
    {
        $url = $this->classificationsUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     */
    public function moderation($opts)
    {
        $url = $this->moderationUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param        $opts
     * @param  null  $stream
     * @return bool|string
     * @throws Exception
     */
    public function chat($opts, $stream = null)
    {
        if ($stream != null && array_key_exists('stream', $opts)) {
            if (!$opts['stream']) {
                throw new Exception(
                    'Please provide a stream function. Check https://github.com/orhanerday/open-ai#stream-example for an example.'
                );
            }

            $this->stream_method = $stream;
        }

        $opts['model'] = $opts['model'] ?? $this->chatModel;
        $url           = $this->chatUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     */
    public function transcribe($opts)
    {
        $url = $this->transcriptionsUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     */
    public function translate($opts)
    {
        $url = $this->translationsUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     */
    public function uploadFile($opts)
    {
        $url = $this->filesUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     */
    public function textToSpeech($opts)
    {
        $url = $this->textToSpeechUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @return bool|string
     */
    public function listFiles()
    {
        $url = $this->filesUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $file_id
     * @return bool|string
     */
    public function retrieveFile($file_id)
    {
        $file_id = "/$file_id";
        $url     = $this->filesUrl().$file_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $file_id
     * @return bool|string
     */
    public function retrieveFileContent($file_id)
    {
        $file_id = "/$file_id/content";
        $url     = $this->filesUrl().$file_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $file_id
     * @return bool|string
     */
    public function deleteFile($file_id)
    {
        $file_id = "/$file_id";
        $url     = $this->filesUrl().$file_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'DELETE');
    }

    /**
     * @param $opts
     * @return bool|string
     */
    public function createFineTune($opts)
    {
        $url = $this->fineTuneUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @return bool|string
     */
    public function listFineTunes()
    {
        $url = $this->fineTuneUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fine_tune_id
     * @return bool|string
     */
    public function retrieveFineTune($fine_tune_id)
    {
        $fine_tune_id = "/$fine_tune_id";
        $url          = $this->fineTuneUrl().$fine_tune_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fine_tune_id
     * @return bool|string
     */
    public function cancelFineTune($fine_tune_id)
    {
        $fine_tune_id = "/$fine_tune_id/cancel";
        $url          = $this->fineTuneUrl().$fine_tune_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST');
    }

    /**
     * @param $fine_tune_id
     * @return bool|string
     */
    public function listFineTuneEvents($fine_tune_id)
    {
        $fine_tune_id = "/$fine_tune_id/events";
        $url          = $this->fineTuneUrl().$fine_tune_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fine_tune_id
     * @return bool|string
     */
    public function deleteFineTune($fine_tune_id)
    {
        $fine_tune_id = "/$fine_tune_id";
        $url          = $this->fineTuneModel().$fine_tune_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'DELETE');
    }

    /**
     * @param
     * @return bool|string
     * @deprecated
     */
    public function engines()
    {
        $url = $this->enginesUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $engine
     * @return bool|string
     * @deprecated
     */
    public function engine($engine)
    {
        $url = $this->engineUrl($engine);
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $opts
     * @return bool|string
     */
    public function embeddings($opts)
    {
        $url = $this->embeddings_url();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param  int  $timeout
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @param  string  $proxy
     */
    public function setProxy(string $proxy)
    {
        if ($proxy && strpos($proxy, '://') === false) {
            $proxy = 'https://'.$proxy;
        }
        $this->proxy = $proxy;
    }

    /**
     * @param  string  $customUrl
     * @deprecated
     */

    /**
     * @param  string  $customUrl
     * @return void
     */
    public function setCustomURL(string $customUrl)
    {
        if ($customUrl != "") {
            $this->customUrl = $customUrl;
        }
    }

    /**
     * @param  string  $customUrl
     * @return void
     */
    public function setBaseURL(string $customUrl)
    {
        if ($customUrl != '') {
            $this->customUrl = $customUrl;
        }
    }

    /**
     * @param  array  $header
     * @return void
     */
    public function setHeader(array $header)
    {
        if ($header) {
            foreach ($header as $key => $value) {
                $this->headers[$key] = $value;
            }
        }
    }

    /**
     * @param  string  $org
     */
    public function setORG(string $org)
    {
        if ($org != "") {
            $this->headers[] = "OpenAI-Organization: $org";
        }
    }

    /**
     * @param  string  $url
     * @param  string  $method
     * @param  array   $opts
     * @return bool|string
     */
    private function sendRequest(string $url, string $method, array $opts = [])
    {
        $post_fields = json_encode($opts);

        if (array_key_exists('file', $opts) || array_key_exists('image', $opts)) {
            $this->headers[0] = $this->contentTypes["multipart/form-data"];
            $post_fields      = $opts;
        } else {
            $this->headers[0] = $this->contentTypes["application/json"];
        }
        $curl_info = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $post_fields,
            CURLOPT_HTTPHEADER     => $this->headers,
        ];

        if ($opts == []) {
            unset($curl_info[CURLOPT_POSTFIELDS]);
        }

        if (!empty($this->proxy)) {
            $curl_info[CURLOPT_PROXY] = $this->proxy;
        }

        if (array_key_exists('stream', $opts) && $opts['stream']) {
            $curl_info[CURLOPT_WRITEFUNCTION] = $this->stream_method;
        }

        $curl = curl_init();

        curl_setopt_array($curl, $curl_info);
        $response = curl_exec($curl);

        $info           = curl_getinfo($curl);
        $this->curlInfo = $info;

        curl_close($curl);

        return $response;
    }

    /**
     * @param  string  $url
     */
    private function baseUrl(string &$url)
    {
        if ($this->customUrl != "") {
            $url = str_replace($this->openAiEndpoint, $this->customUrl, $url);
        }
    }

    /**
     * @deprecated
     * @param string $engine
     * @return string
     */
    public function completionURL(string $engine): string
    {
        return $this->openAiEndpoint . "/engines/$engine/completions";
    }

    /**
     * @return string
     */
    public function completionsURL(): string
    {
        return $this->openAiEndpoint . "/completions";
    }

    /**
     *
     * @return string
     */
    public function editsUrl(): string
    {
        return $this->openAiEndpoint . "/edits";
    }

    /**
     * @param string $engine
     * @return string
     */
    public function searchURL(string $engine): string
    {
        return $this->openAiEndpoint . "/engines/$engine/search";
    }

    /**
     * @param
     * @return string
     */
    public function enginesUrl(): string
    {
        return $this->openAiEndpoint . "/engines";
    }

    /**
     * @param string $engine
     * @return string
     */
    public function engineUrl(string $engine): string
    {
        return $this->openAiEndpoint . "/engines/$engine";
    }

    /**
     * @param
     * @return string
     */
    public function classificationsUrl(): string
    {
        return $this->openAiEndpoint . "/classifications";
    }

    /**
     * @param
     * @return string
     */
    public function moderationUrl(): string
    {
        return $this->openAiEndpoint . "/moderations";
    }

    /**
     * @param
     * @return string
     */
    public function transcriptionsUrl(): string
    {
        return $this->openAiEndpoint . "/audio/transcriptions";
    }

    /**
     * @param
     * @return string
     */
    public function translationsUrl(): string
    {
        return $this->openAiEndpoint . "/audio/translations";
    }

    /**
     * @param
     * @return string
     */
    public function filesUrl(): string
    {
        return $this->openAiEndpoint . "/files";
    }

    /**
     * @param
     * @return string
     */
    public function fineTuneUrl(): string
    {
        return $this->openAiEndpoint . "/fine-tunes";
    }

    /**
     * @param
     * @return string
     */
    public function fineTuneModel(): string
    {
        return $this->openAiEndpoint . "/models";
    }

    /**
     * @param
     * @return string
     */
    public function answersUrl(): string
    {
        return $this->openAiEndpoint . "/answers";
    }

    /**
     * @param
     * @return string
     */
    public function imageUrl(): string
    {
        return $this->openAiEndpoint . "/images";
    }

    /**
     * @param
     * @return string
     */
    public function embeddings_url(): string
    {
        return $this->openAiEndpoint . "/embeddings";
    }

    /**
     * @param
     * @return string
     */
    public function chatUrl(): string
    {
        return $this->openAiEndpoint . "/chat/completions";
    }

    /**
     * @param
     * @return string
     */
    public function textToSpeechUrl(): string
    {
        return $this->openAiEndpoint . "/audio/speech";
    }
    
}