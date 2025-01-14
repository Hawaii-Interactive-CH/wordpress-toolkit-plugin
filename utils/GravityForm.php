<?php

namespace Toolkit\utils;

use Toolkit\models\Config;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;


class GravityForm
{
    const API_KEY = '';
    const PRIVATE_KEY = '';
    const METHOD = 'POST';
    const ROUTE = 'forms/1/submissions';
    const PATH = 'gf/v2/';
    const ROUTE_CREATE = 'forms';

    public $api_key;
    public $private_key;

    public function __construct()
    {
        $this->api_key = Config::acf('gravity_api_key');
        $this->private_key = Config::acf('gravity_api_secret');
    }

    public static function auth()
    {
        $expires = strtotime("+60 mins");
        $toSign = sprintf(
            "%s:%s:%s:%s",
            Config::acf('gravity_api_key'),
            self::METHOD,
            self::ROUTE,
            $expires
        );
        return [
            "api_key" => Config::acf('gravity_api_key'),
            "expires" => $expires,
            "signature" => self::calculate_signature(
                $toSign,
                Config::acf('gravity_private_key')
            ),
        ];
    }

    private static function calculate_signature($toSign, $privateKey)
    {
        $hash = hash_hmac("sha1", $toSign, $privateKey, true);
        return rawurlencode(base64_encode($hash));
    }

    public static function get_form($entry)
    {
        $url = self::getBaseUrl()  . self::PATH . 'forms/' . $entry;

        $credentials = Config::acf('gravity_api_key') . ':' . Config::acf('gravity_private_key');
        $encoded_credentials = base64_encode($credentials);

        $headers = array(
            'Authorization' => 'Basic ' . $encoded_credentials,
        );

        $response = wp_remote_get($url, [
            'method' => 'GET',
            'headers' => $headers,
        ]);

        if (is_wp_error($response)) {
            // Log error or handle it accordingly
            error_log($response->get_error_message());
            return 0;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['id'])) {
            return $body;
        }
    }


    public static function create(string $title, array $fields, array $notifications, bool $is_active): int
    {
        if (empty($title) || empty($fields)) {
            return 0;
        }


        $url = self::getBaseUrl() . self::PATH . self::ROUTE_CREATE;

        $credentials = Config::acf('gravity_api_key') . ':' . Config::acf('gravity_private_key');
        $encoded_credentials = base64_encode($credentials);

        $headers = array(
            'Authorization' => 'Basic ' . $encoded_credentials,
            'Content-Type' => 'application/json'
        );

        $body = [
            'title' => $title,
            'fields' => self::format_fields($fields),
            'notifications' => self::format_notifications($notifications),
            'button' => [
                'type' => 'text',
                'text' => __('Envoyer', 'toolkit'),
            ],
            'is_active' => $is_active ? '1' : '0',
            'date_created' => date('Y-m-d H:i:s'),
        ];

        // error_log(json_encode($body));

        // The 'body' parameter is converted to JSON.
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($body)
        ));

        // error_log(json_encode($response));

        if (is_wp_error($response)) {
            // Log error or handle it accordingly
            error_log($response->get_error_message());
            return 0;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['id'])) {
            return $body['id'];
        }

        // Log unexpected response
        // error_log('Unexpected response: ' . wp_remote_retrieve_body($response));
        return 0;
    }

    public static function update(string $title, array $fields, array $notifications, bool $is_active, int $form_id): int
    {
        if (empty($title) || empty($fields)) {
            return 0;
        }

        $url = self::getBaseUrl() . self::PATH . self::ROUTE_CREATE . '/' . $form_id;

        $credentials = Config::acf('gravity_api_key') . ':' . Config::acf('gravity_private_key');
        $encoded_credentials = base64_encode($credentials);

        $headers = array(
            'Authorization' => 'Basic ' . $encoded_credentials,
            'Content-Type' => 'application/json'
        );

        $body = [
            'title' => $title,
            'fields' => self::format_fields($fields),
            'notifications' => self::format_notifications($notifications),
            'button' => [
                'type' => 'text',
                'text' => __('Envoyer', 'toolkit'),
            ],
            'is_active' => $is_active ? '1' : '0',
            'date_created' => date('Y-m-d H:i:s'),
        ];

        // error_log(json_encode($body));

        // The 'body' parameter is converted to JSON.
        $response = wp_remote_request($url, array(
            'method' => 'PUT',
            'headers' => $headers,
            'body' => json_encode($body)
        ));

        // error_log(json_encode($response));

        if (is_wp_error($response)) {
            // Log error or handle it accordingly
            error_log($response->get_error_message());
            return 0;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['id'])) {
            return $body['id'];
        }

        // Log unexpected response
        // error_log('Unexpected response: ' . wp_remote_retrieve_body($response));
        return 0;
    }

    public static function format_fields(array $fields): array
    {
        $formattedFields = ['fields' => []];

        foreach ($fields as $index => $fieldArray) {
            $field = $fieldArray['item'];

            $formattedField = [
                'id' => $index + 1, // l'ID est basé sur l'index du tableau + 1
                'label' => htmlspecialchars_decode($field['label']), // Convertir les entités HTML
                'adminLabel' => '',
                'type' => $field['type'],
                'isRequired' => $field['isRequired'],
            ];

            // Process 'choices' for applicable field types
            if (in_array($field['type'], ['select', 'checkbox', 'radio'])) {
                $formattedChoices = [];
                foreach ($field['choices'] as $choice) {
                    $formattedChoices[] = [
                        'text' => $choice['item'], // The visible text in form
                        'value' => $choice['item'], // The value to store in the database; adjust as needed
                        'isSelected' => false, // Default to not selected; adjust as needed
                    ];
                }
                // hide field if it is hidden
                if (isset($field['isHidden']) && $field['isHidden']) {
                    $formattedField['cssClass'] = 'hidden';
                }
                $formattedField['choices'] = $formattedChoices;
            }

            $formattedFields['fields'][] = $formattedField;
        }

        // log the formatted fields
        error_log(json_encode($formattedFields));

        return $formattedFields['fields'];
    }

    public static function format_notifications(array $fields): array
    {
        $formattedFields = ['notifications' => []];

        // Add user notification
        $index = 0;
        foreach ($fields as $notification) {
            $formattedFields['notifications'][$index] = [
                'isActive' => $notification['isActive'] ?? true,
                'id' => $index,
                'name' => $notification['name_admin'] ?? 'Admin Notification',
                'event' => 'form_submission',
                'to' => $notification['to'] ?? '{admin_email}',
                'toType' => 'email',
                'subject' => $notification['subject_admin'] ?? 'Nouvelle soumission - {form_title}',
                'message' => $notification['message_admin']  ?? '{all_fields}',
                'from' => '{admin_email}',
                'conditionalLogic' =>  $notification['conditionalLogic'] ?? null,
                'disableAutoformat' => false,
                'enableAttachments' => false
            ];

            $index++;
            $formattedFields['notifications'][$index] = [
                'isActive' => $notification['isActive'] ?? true,
                'id' => $index,
                'name' => $notification['name_user'] ?? 'User Notification',
                'event' => 'form_submission',
                'to' => '3',
                'toType' => 'field',
                'subject' => $notification['subject_user'] ?? 'Merci pour votre soumission au formulaire: {form_title}',
                'message' => $notification['message_user'] ?? "Bonjour,\n{all_fields}\n Merci de contacter " . $notification['to'] . " pour plus d'informations ou pour annuler votre réservation.",
                'from' => '{admin_email}',
                'conditionalLogic' => $notification['conditionalLogic'] ?? null,
                'disableAutoformat' => false,
                'enableAttachments' => false,
            ];

            $index++;
        }
        

        // log the formatted fields
        // error_log(json_encode($formattedFields));

        return $formattedFields['notifications'];
    }

    public static function get_last_updated($post_id)
    {
        // get post last modified date
        $last_modified = get_the_modified_date('Y-m-d H:i:s', $post_id);
        return $last_modified;
    }

    public static function count_entries_after_submission($entry, $form)
    {
        $target_field_id = '1'; // The field ID you are always interested in

        // Retrieve the submitted value for the target field
        $value_to_match = rgar($entry, $target_field_id); // Using rgar() for safe access

        // Get the form ID from the submission
        $target_form_id = $form['id'];

        // Define search criteria
        $search_criteria = [
            'status'        => 'active',
            'field_filters' => [
                [
                    'key'   => $target_field_id,
                    'value' => $value_to_match,
                ],
            ],
        ];

        // Count entries
        $total_count = \GFAPI::count_entries($target_form_id, $search_criteria);

        // Use the count
        error_log("Total Entries with '{$value_to_match}' in field {$target_field_id} for form {$target_form_id}: {$total_count}");
    }

    public static function all_active()
    {
        /**
         * list all GF forms id and title
         */
        $myforms = \RGFormsModel::get_forms();
        $items = [];
        foreach ($myforms as $myform) {
            // save title and id in an array if the form is active
            if ($myform->is_active) {
                $items[] = (object) [
                    'id' => $myform->id,
                    'title' => $myform->title,
                ];
            }
        }

        // sort the array by title
        usort($items, function ($a, $b) {
            return $a->title <=> $b->title;
        });

        // allow null value as first option
        array_unshift($items, (object) ['id' => null, 'title' => 'Please select a form']);

        return (object) $items;
    }


    public static function getBaseUrl()
    {
        // if hostname => hawai.li
        return get_rest_url();
    }

}
