<?php

namespace App\Services\WhatsApp;

interface WhatsAppClientInterface
{
    /**
     * Test connection to the WhatsApp provider/server.
     *
     * @param  array{url?: string, api_key?: string, session_id?: string}  $config
     * @return array{success: bool, message: string, data?: array, device_state?: string}
     */
    public function testConnection(array $config): array;

    /**
     * Get WhatsApp device / session connection status.
     *
     * @param  array{url?: string, api_key?: string, session_id?: string}  $config
     * @return array{connected: bool, device_id?: string, state?: string, jid?: string}
     */
    public function getDeviceStatus(array $config): array;

    /**
     * Read / fetch chat message history for a given phone number or JID.
     *
     * @param  string  $number  Phone number or JID
     * @param  int  $limit  Maximum number of messages to retrieve
     * @param  array{url?: string, api_key?: string, session_id?: string, offset?: int, search?: string, media_only?: bool}  $options
     * @return array{success: bool, phone: string, jid?: string, messages: array, message?: string}
     */
    public function getMessages(string $number, int $limit = 50, array $options = []): array;

    /**
     * Send a text message to a given phone number or JID.
     *
     * @param  string  $number  Phone number or JID
     * @param  string  $message  Message text
     * @param  array{url?: string, api_key?: string, session_id?: string, reply_message_id?: string, mentions?: array}  $options
     * @return array{success: bool, message: string, data?: array}
     */
    public function sendMessage(string $number, string $message, array $options = []): array;

    /**
     * Send a media message (image/file/document/audio/video) via multipart/form-data.
     *
     * @param  string  $number  Phone number or JID
     * @param  string  $mediaUrl  File URL or local file path
     * @param  string  $caption  Caption text
     * @param  string  $mediaType  image|file|document|audio|video|sticker
     * @param  array{url?: string, api_key?: string, session_id?: string, view_once?: bool, compress?: bool, reply_message_id?: string}  $options
     * @return array{success: bool, message: string, data?: array}
     */
    public function sendMedia(string $number, string $mediaUrl, string $caption = '', string $mediaType = 'image', array $options = []): array;

    /**
     * Check if a phone number is registered on WhatsApp.
     *
     * @param  string  $number  Phone number
     * @param  array{url?: string, api_key?: string, session_id?: string}  $options
     * @return array{success: bool, on_whatsapp: bool, jid?: string, message?: string}
     */
    public function checkUser(string $number, array $options = []): array;

    /**
     * Get user profile avatar/picture from WhatsApp.
     *
     * @param  string  $number  Phone number
     * @param  array{url?: string, api_key?: string, session_id?: string, is_preview?: bool}  $options
     * @return array{success: bool, url?: string, message?: string}
     */
    public function getUserAvatar(string $number, array $options = []): array;

    /**
     * Get user WhatsApp profile information.
     *
     * @param  string  $number  Phone number
     * @param  array{url?: string, api_key?: string, session_id?: string}  $options
     * @return array{success: bool, info?: array, message?: string}
     */
    public function getUserInfo(string $number, array $options = []): array;

    /**
     * Get login QR code for device pairing.
     *
     * @param  array{url?: string, api_key?: string, session_id?: string}  $config
     * @return array{success: bool, qr_link?: string, qr_duration?: int, message?: string}
     */
    public function getLoginQr(array $config = []): array;

    /**
     * Mark a message as read.
     *
     * @param  string  $messageId  Message ID
     * @param  string  $number  Phone number
     * @param  array{url?: string, api_key?: string, session_id?: string}  $options
     * @return array{success: bool, message?: string}
     */
    public function markAsRead(string $messageId, string $number, array $options = []): array;

    /**
     * Send typing / presence indicator.
     *
     * @param  string  $number  Phone number
     * @param  string  $action  start|stop
     * @param  array{url?: string, api_key?: string, session_id?: string}  $options
     * @return array{success: bool, message?: string}
     */
    public function sendChatPresence(string $number, string $action = 'start', array $options = []): array;
}
