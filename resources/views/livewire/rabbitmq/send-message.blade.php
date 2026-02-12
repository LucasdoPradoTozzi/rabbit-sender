<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">RabbitMQ - Enviar Mensagem</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Envie mensagens para processar vagas ou marcar como concluídas
        </p>
    </div>

    {{-- Success Message --}}
    @if ($showSuccess)
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center gap-2 text-green-800 dark:text-green-200">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Mensagem enviada com sucesso para a fila <strong>{{ $successQueue }}</strong>!</span>
            </div>
            <button
                type="button"
                wire:click="dismissSuccess"
                class="text-green-700 dark:text-green-300 hover:text-green-900 dark:hover:text-green-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    @endif

    {{-- Error Message --}}
    @if ($showError)
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-start gap-2 text-red-800 dark:text-red-200">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <strong>Erro ao enviar mensagem</strong>
                    @if ($errorMessage)
                    <p class="text-sm mt-1">{{ $errorMessage }}</p>
                    @endif
                </div>
            </div>
            <button
                type="button"
                wire:click="dismissError"
                class="text-red-700 dark:text-red-300 hover:text-red-900 dark:hover:text-red-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    @endif

    <div class="bg-white dark:bg-zinc-800 shadow-sm rounded-lg p-6">
        <form wire:submit="sendMessage" class="space-y-6">

            {{-- Message Type Selector --}}
            <div>
                <flux:radio.group wire:model.live="messageType" label="Tipo de Mensagem" variant="segmented">
                    <flux:radio value="mark-job-done" label="Marcar como Concluída" />
                    <flux:radio value="process-jobs" label="Processar Vaga" />
                </flux:radio.group>
            </div>

            <flux:separator />

            {{-- mark-job-done Form --}}
            @if ($messageType === 'mark-job-done')
            <div class="space-y-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <flux:icon.information-circle class="w-4 h-4 inline mr-1" />
                        Envie o ID ou UUID da vaga para marcá-la como concluída na fila <strong>mark-job-done</strong>
                    </p>
                </div>

                <div>
                    <flux:input
                        wire:model="jobId"
                        label="ID/UUID da Vaga"
                        type="text"
                        required
                        placeholder="ex: 12345 ou 550e8400-e29b-41d4-a716-446655440000"
                        description="Identificador único da vaga que foi concluída" />
                </div>
            </div>
            @endif

            {{-- process-jobs Form --}}
            @if ($messageType === 'process-jobs')
            <div class="space-y-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <flux:icon.information-circle class="w-4 h-4 inline mr-1" />
                        Preencha os dados da vaga para processamento. É obrigatório enviar pelo menos o texto da vaga ou imagens.
                    </p>
                </div>

                {{-- Link --}}
                <div>
                    <flux:input
                        wire:model="link"
                        label="Link da Vaga"
                        type="url"
                        required
                        placeholder="https://exemplo.com/vaga/123"
                        description="URL completa da página da vaga" />
                </div>

                {{-- Email (read-only, auto-filled) --}}
                <div>
                    <flux:input
                        value="{{ auth()->user()->email }}"
                        label="Email"
                        type="email"
                        disabled
                        description="Seu email será incluído automaticamente na mensagem" />
                </div>

                {{-- Job Info --}}
                <div>
                    <flux:textarea
                        wire:model="jobInfo"
                        label="Descrição da Vaga"
                        rows="6"
                        placeholder="Cole aqui o texto completo da vaga..."
                        description="Texto da vaga (obrigatório caso não envie imagens)" />
                </div>

                {{-- Images --}}
                <div>
                    <flux:field>
                        <flux:label>Imagens da Vaga</flux:label>
                        <flux:description>Envie capturas de tela ou imagens da vaga (obrigatório caso não envie texto)</flux:description>

                        <div class="mt-2">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <flux:icon.photo class="w-8 h-8 text-gray-400 mb-2" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-semibold">Clique para selecionar</span> ou arraste imagens
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">PNG, JPG, GIF até 5MB cada</p>
                                </div>
                                <input type="file" wire:model="images" multiple accept="image/*" class="hidden" />
                            </label>
                        </div>

                        {{-- Preview de imagens selecionadas --}}
                        @if (!empty($images))
                        <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach ($images as $index => $image)
                            <div class="relative group">
                                <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="w-full h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                <button
                                    type="button"
                                    wire:click="removeImage({{ $index }})"
                                    class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <flux:icon.x-mark class="w-4 h-4" />
                                </button>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">{{ $image->getClientOriginalName() }}</p>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @error('images.*')
                        <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center gap-4 pt-4">
                <flux:button
                    variant="primary"
                    type="submit">
                    <flux:icon.paper-airplane class="w-4 h-4 mr-2" />
                    Enviar para Fila
                </flux:button>

                <flux:button
                    variant="ghost"
                    type="button"
                    wire:click="clearForm">
                    Limpar
                </flux:button>
            </div>

        </form>
    </div>

    {{-- Info Cards --}}
    <div class="mt-8 grid md:grid-cols-2 gap-6">
        {{-- mark-job-done info --}}
        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-6">
            <div class="flex items-start gap-3">
                <flux:icon.check-badge class="w-6 h-6 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-0.5" />
                <div>
                    <h3 class="font-semibold text-purple-900 dark:text-purple-100 mb-2">Marcar como Concluída</h3>
                    <div class="text-sm text-purple-800 dark:text-purple-200">
                        <p class="mb-2">Fila: <code class="bg-purple-100 dark:bg-purple-900/40 px-2 py-0.5 rounded">mark-job-done</code></p>
                        <p>Envie o ID ou UUID da vaga que já foi processada e precisa ser marcada como concluída no sistema.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- process-jobs info --}}
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
            <div class="flex items-start gap-3">
                <flux:icon.cog class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                <div>
                    <h3 class="font-semibold text-green-900 dark:text-green-100 mb-2">Processar Vaga</h3>
                    <div class="text-sm text-green-800 dark:text-green-200">
                        <p class="mb-2">Fila: <code class="bg-green-100 dark:bg-green-900/40 px-2 py-0.5 rounded">process-jobs</code></p>
                        <p>Envie uma nova vaga para processamento. Deve conter o link, e pelo menos o texto da vaga ou imagens em anexo.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>