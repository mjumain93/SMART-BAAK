<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    private $apiBaseUrl;
    private $sessionId;

    public function __construct()
    {
        $this->apiBaseUrl = env('WA_BOT_API_URL', 'http://localhost:3000');
        $this->sessionId = env('WA_SESSION_ID', 'default');
    }

    public function index()
    {
        $qrDataUri = null;
        $response = Http::get("{$this->apiBaseUrl}/qr/{$this->sessionId}");
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['qr']) && $data['qr']) {
                $qrDataUri = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($data['qr']);
            }
        }
        return view('whatsapp.whatsapp', compact('qrDataUri'));
    }

    public function sendText(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
            'message' => 'required|string',
        ]);

        $response = Http::post("{$this->apiBaseUrl}/send-text/{$this->sessionId}", [
            'number' => $request->number,
            'message' => $request->message,
        ]);

        return $this->handleResponse($response, 'Pesan teks terkirim', 'Gagal mengirim pesan');
    }

    public function sendMedia(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
            'media' => 'required|file',
            'caption' => 'nullable|string',
        ]);

        $file = $request->file('media');
        $base64 = base64_encode(file_get_contents($file->getRealPath()));
        $filename = $file->getClientOriginalName();

        $response = Http::post("{$this->apiBaseUrl}/send-media/{$this->sessionId}", [
            'number' => $request->number,
            'base64Data' => $base64,
            'filename' => $filename,
            'caption' => $request->caption ?? '',
        ]);

        return $this->handleResponse($response, 'Media terkirim', 'Gagal mengirim media');
    }

    public function sendLocation(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'nullable|string',
        ]);

        $response = Http::post("{$this->apiBaseUrl}/send-location/{$this->sessionId}", $request->only(['number', 'latitude', 'longitude', 'description']));

        return $this->handleResponse($response, 'Lokasi terkirim', 'Gagal mengirim lokasi');
    }

    public function sendContact(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
            'contactNumber' => 'required|string',
            'contactName' => 'required|string',
        ]);

        $response = Http::post("{$this->apiBaseUrl}/send-contact/{$this->sessionId}", $request->only(['number', 'contactNumber', 'contactName']));

        return $this->handleResponse($response, 'Kontak terkirim', 'Gagal mengirim kontak');
    }

    public function sendMention(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
            'message' => 'required|string',
            'mentionedNumbers' => 'nullable|string', // CSV numbers
        ]);

        $mentionedNumbers = [];
        if ($request->mentionedNumbers) {
            $mentionedNumbers = array_map('trim', explode(',', $request->mentionedNumbers));
        }

        $response = Http::post("{$this->apiBaseUrl}/send-mention/{$this->sessionId}", [
            'number' => $request->number,
            'message' => $request->message,
            'mentionedNumbers' => $mentionedNumbers,
        ]);

        return $this->handleResponse($response, 'Mention terkirim', 'Gagal mengirim mention');
    }

    public function sendButtons(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
            'text' => 'required|string',
            'buttons' => 'required|array',
            'buttons.*.id' => 'required|string',
            'buttons.*.body' => 'required|string',
        ]);

        $response = Http::post("{$this->apiBaseUrl}/send-buttons/{$this->sessionId}", [
            'number' => $request->number,
            'text' => $request->text,
            'buttons' => $request->buttons,
        ]);

        return $this->handleResponse($response, 'Buttons terkirim', 'Gagal mengirim buttons');
    }

    public function sendList(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
            'text' => 'required|string',
            'buttonText' => 'required|string',
            'sections' => 'required|array',
            'sections.*.title' => 'required|string',
            'sections.*.rows' => 'required|array',
            'sections.*.rows.*.id' => 'required|string',
            'sections.*.rows.*.title' => 'required|string',
            'sections.*.rows.*.description' => 'nullable|string',
        ]);

        $response = Http::post("{$this->apiBaseUrl}/send-list/{$this->sessionId}", [
            'number' => $request->number,
            'text' => $request->text,
            'buttonText' => $request->buttonText,
            'sections' => $request->sections,
        ]);

        return $this->handleResponse($response, 'List terkirim', 'Gagal mengirim list');
    }

    public function sendGroupMessage(Request $request)
    {
        $request->validate([
            'groupName' => 'required|string',
            'message' => 'required|string',
        ]);

        $response = Http::post("{$this->apiBaseUrl}/send-group-message/{$this->sessionId}", [
            'groupName' => $request->groupName,
            'message' => $request->message,
        ]);

        return $this->handleResponse($response, 'Pesan grup terkirim', 'Gagal mengirim pesan grup');
    }

    public function blastMessages(Request $request)
    {
        $request->validate([
            'contacts' => 'required|array',
            'contacts.*.number' => 'required|string',
            'contacts.*.name' => 'nullable|string',
            'messageTemplate' => 'required|string',
            'delay' => 'nullable|integer',
        ]);

        $response = Http::post("{$this->apiBaseUrl}/blast-messages/{$this->sessionId}", [
            'contacts' => $request->contacts,
            'messageTemplate' => $request->messageTemplate,
            'delay' => $request->delay ?? 1000,
        ]);

        return $this->handleResponse($response, 'Blast pesan selesai', 'Gagal blast pesan');
    }

    private function handleResponse($response, $successMsg, $errorMsg)
    {
        if ($response->successful()) {
            return back()->with('status', $response->json('message', $successMsg));
        }
        return back()->with('error', $response->json('message', $errorMsg));
    }
}
