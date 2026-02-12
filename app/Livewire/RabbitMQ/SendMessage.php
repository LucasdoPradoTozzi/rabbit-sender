<?php

namespace App\Livewire\RabbitMQ;

use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class SendMessage extends Component
{
    use WithFileUploads;

    // Tipo de mensagem
    public string $messageType = 'mark-job-done';

    // mark-job-done fields
    public string $jobId = '';

    // process-jobs fields
    public string $link = '';
    public string $jobInfo = '';
    public array $images = [];

    // Feedback
    public bool $showSuccess = false;
    public bool $showError = false;
    public string $errorMessage = '';
    public string $successQueue = '';

    /**
     * Send message to RabbitMQ
     */
    public function sendMessage(): void
    {
        $this->showSuccess = false;
        $this->showError = false;
        $this->errorMessage = '';

        if ($this->messageType === 'mark-job-done') {
            $this->sendMarkJobDone();
        } elseif ($this->messageType === 'process-jobs') {
            $this->sendProcessJobs();
        }
    }

    /**
     * Send mark-job-done message
     */
    protected function sendMarkJobDone(): void
    {
        $this->validate([
            'jobId' => 'required|string|max:255',
        ], [
            'jobId.required' => 'O ID/UUID é obrigatório.',
            'jobId.max' => 'O ID/UUID não pode ter mais de 255 caracteres.',
        ]);

        try {
            $rabbitmq = new RabbitMQService();

            $message = [
                'job_id' => $this->jobId,
            ];

            $success = $rabbitmq->sendMessage('mark-job-done', $message);

            if ($success) {
                $this->successQueue = 'mark-job-done';
                $this->showSuccess = true;
                $this->reset(['jobId']);
            } else {
                $this->showError = true;
                $this->errorMessage = 'Falha ao enviar mensagem. Verifique os logs.';
            }
        } catch (\Exception $e) {
            $this->showError = true;
            $this->errorMessage = 'Erro: ' . $e->getMessage();
        }
    }

    /**
     * Send process-jobs message
     */
    protected function sendProcessJobs(): void
    {
        $this->validate([
            'link' => 'required|url|max:2048',
            'jobInfo' => 'required_without:images|string',
            'images.*' => 'nullable|image|max:5120', // Max 5MB per image
        ], [
            'link.required' => 'O link é obrigatório.',
            'link.url' => 'O link deve ser uma URL válida.',
            'link.max' => 'O link não pode ter mais de 2048 caracteres.',
            'jobInfo.required_without' => 'É necessário informar o texto da vaga ou anexar imagens.',
            'images.*.image' => 'Todos os arquivos devem ser imagens.',
            'images.*.max' => 'Cada imagem não pode ter mais de 5MB.',
        ]);

        try {
            $rabbitmq = new RabbitMQService();

            $message = [
                'link' => $this->link,
                'email' => Auth::user()->email,
            ];

            // Adiciona job-info se preenchido
            if (!empty($this->jobInfo)) {
                $message['job_info'] = $this->jobInfo;
            }

            // Converte imagens para base64
            if (!empty($this->images)) {
                $imagesBase64 = [];

                foreach ($this->images as $image) {
                    $imageContent = file_get_contents($image->getRealPath());
                    $base64 = base64_encode($imageContent);
                    $mimeType = $image->getMimeType();

                    $imagesBase64[] = [
                        'filename' => $image->getClientOriginalName(),
                        'mime_type' => $mimeType,
                        'data' => $base64,
                    ];
                }

                $message['images'] = $imagesBase64;
            }

            $success = $rabbitmq->sendMessage('process-jobs', $message);

            if ($success) {
                $this->successQueue = 'process-jobs';
                $this->showSuccess = true;
                $this->reset(['link', 'jobInfo', 'images']);
            } else {
                $this->showError = true;
                $this->errorMessage = 'Falha ao enviar mensagem. Verifique os logs.';
            }
        } catch (\Exception $e) {
            $this->showError = true;
            $this->errorMessage = 'Erro: ' . $e->getMessage();
        }
    }

    /**
     * Clear form
     */
    public function clearForm(): void
    {
        $this->reset(['jobId', 'link', 'jobInfo', 'images', 'showSuccess', 'showError', 'errorMessage', 'successQueue']);
    }

    /**
     * Remove a specific image from the array
     */
    public function removeImage($index): void
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    /**
     * Dismiss success message
     */
    public function dismissSuccess(): void
    {
        $this->showSuccess = false;
        $this->successQueue = '';
    }

    /**
     * Dismiss error message
     */
    public function dismissError(): void
    {
        $this->showError = false;
        $this->errorMessage = '';
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.rabbitmq.send-message');
    }
}
