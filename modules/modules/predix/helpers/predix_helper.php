<?php

function predixAISizeOfImages()
{

    $supportedSizes = get_option('predix_image_generator_allowed_image_sizes');

    if (!is_admin()) {
        $supportedSizesArr = explode(',', $supportedSizes);

        $array = [];
        foreach ($supportedSizesArr as $size) {
            $array[] = [
                'value' => $size,
                'name' => $size
            ];
        }
        return $array;
    }

    return [
        [
            'value' => '256x256',
            'name' => '256x256'
        ],
        [
            'value' => '512x512',
            'name' => '512x512'
        ],
        [
            'value' => '1024x1024',
            'name' => '1024x1024'
        ]
    ];
}

/**
 * Check if extension is allowed for upload
 * @param  string $filename filename
 * @return boolean
 */
function predix_translations_upload_extension_allowed($filename)
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $browser = get_instance()->agent->browser();

    $allowed_extensions = explode(',', get_option('predix_audio_translation_allowed_extensions'));
    $allowed_extensions = array_map('trim', $allowed_extensions);

    //  https://discussions.apple.com/thread/7229860
    //  Used in main.js too for Dropzone
    if (strtolower($browser) === 'safari'
        && in_array('.jpg', $allowed_extensions)
        && !in_array('.jpeg', $allowed_extensions)
    ) {
        $allowed_extensions[] = '.jpeg';
    }
    // Check for all cases if this extension is allowed
    if (!in_array('.' . $extension, $allowed_extensions)) {
        return false;
    }

    return true;
}


/**
 * Check if extension is allowed for upload
 * @param  string $filename filename
 * @return boolean
 */
function predix_transcriptions_upload_extension_allowed($filename)
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $browser = get_instance()->agent->browser();

    $allowed_extensions = explode(',', get_option('predix_audio_transcription_allowed_extensions'));
    $allowed_extensions = array_map('trim', $allowed_extensions);

    //  https://discussions.apple.com/thread/7229860
    //  Used in main.js too for Dropzone
    if (strtolower($browser) === 'safari'
        && in_array('.jpg', $allowed_extensions)
        && !in_array('.jpeg', $allowed_extensions)
    ) {
        $allowed_extensions[] = '.jpeg';
    }
    // Check for all cases if this extension is allowed
    if (!in_array('.' . $extension, $allowed_extensions)) {
        return false;
    }

    return true;
}

function predix_download_content_with_curl($url){

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

function predix_template_priorities()
{
    return [
        [
            'value' => 'high',
            'name' => 'High'
        ],
        [
            'value' => 'average',
            'name' => 'Average'
        ],
        [
            'value' => 'low',
            'name' => 'Low'
        ]
    ];
}

function predix_template_tone_of_voices() {
    return [
        [
            'value' => 'funny',
            'name' => 'Funny'
        ],
        [
            'value' => 'casual',
            'name' => 'Casual'
        ],
        [
            'value' => 'excited',
            'name' => 'Excited'
        ],
        [
            'value' => 'professional',
            'name' => 'Professional'
        ],
        [
            'value' => 'witty',
            'name' => 'Witty'
        ],
        [
            'value' => 'sarcastic',
            'name' => 'Sarcastic'
        ],
        [
            'value' => 'feminine',
            'name' => 'Feminine'
        ],
        [
            'value' => 'masculine',
            'name' => 'Masculine'
        ],
        [
            'value' => 'bold',
            'name' => 'Bold'
        ],
        [
            'value' => 'dramatic',
            'name' => 'Dramatic'
        ],
        [
            'value' => 'gumpy',
            'name' => 'Gumpy'
        ],
        [
            'value' => 'secretive',
            'name' => 'Secretive'
        ]
    ];
}

function predix_custom_inputs_allowed_types()
{
    return [
        [
            'value' => 'text',
            'name' => 'Text'
        ],
        [
            'value' => 'number',
            'name' => 'Number'
        ],
        [
            'value' => 'textarea',
            'name' => 'Textarea'
        ]
    ];
}

function predix_supported_chat_models()
{
    return [
        [
            'value' => 'gpt-3.5-turbo',
            'name' => 'GPT-3.5-turbo'
        ],
        [
            'value' => 'gpt-4',
            'name' => 'GPT-4'
        ],
        [
            'value' => 'gpt-4-vision-preview',
            'name' => 'GPT-4-vision-preview'
        ],
        [
            'value' => 'gpt-4-turbo-preview',
            'name' => 'GPT-4-turbo-preview'
        ],
        [
            'value' => 'gpt-4-0125-preview',
            'name' => 'GPT-4-0125-preview'
        ]
    ];
}

function predix_supported_image_models()
{
    return [
        [
            'value' => 'default',
            'name' => 'Default'
        ],
        [
            'value' => 'dall-e-3',
            'name' => 'DALL-E-3'
        ],
        [
            'value' => 'dall-e-2',
            'name' => 'DALL-E-2'
        ]
    ];
}