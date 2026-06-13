@extends('seller.layouts.app')

@section('title', 'Token Energie')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-5 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Token Energie</h1>
        </div>
        <a href="{{ route('seller.sales.history') }}" class="mt-4 inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-gray-50 sm:mt-0">
            Historique
        </a>
    </div>

    @if($errors->hasAny(['cash_register', 'token_api', 'payment_method', 'meter_address', 'room_number', 'amount', 'customer_phone', 'sms_phone']))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('token_sale'))
        @php($tokenSale = session('token_sale'))
        <div x-data="{ show: true }" x-show="show" x-cloak class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-bold text-emerald-700">Token vendu avec succes</p>
                    <p class="mt-2 font-mono text-2xl font-extrabold tracking-wide text-gray-950">{{ $tokenSale['token'] }}</p>
                    <p class="mt-2 text-sm text-gray-700">
                        {{ $tokenSale['address'] }} - Numéro du bien {{ $tokenSale['room'] }} -
                        {{ number_format($tokenSale['amount'], 0, ',', ' ') }} FCFA -
                        @if(($tokenSale['operator_fee'] ?? 0) > 0)
                            Frais {{ number_format($tokenSale['operator_fee'], 0, ',', ' ') }} FCFA -
                        @endif
                        @if(($tokenSale['sms_fee'] ?? 0) > 0)
                            SMS {{ number_format($tokenSale['sms_fee'], 0, ',', ' ') }} FCFA -
                        @endif
                        @if(($tokenSale['operator_fee'] ?? 0) > 0 || ($tokenSale['sms_fee'] ?? 0) > 0)
                            Total {{ number_format($tokenSale['total'], 0, ',', ' ') }} FCFA -
                        @endif
                        {{ number_format($tokenSale['kwh'], 2, ',', ' ') }} kWh -
                        {{ $tokenSale['payment_method'] ?? 'Especes' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ $tokenSale['receipt_url'] }}" target="_blank" class="inline-flex items-center justify-center rounded-md bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700">
                        Recu
                    </a>
                    <button type="button" @click="show = false" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-emerald-200 bg-white text-xl font-bold leading-none text-emerald-700 shadow-sm transition hover:bg-emerald-100" aria-label="Fermer le token vendu" title="Fermer">
                        &times;
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div
        x-data="{
            tokenSale: null,
            formatMoney(value) {
                return new Intl.NumberFormat('fr-FR').format(Number(value || 0)) + ' FCFA';
            },
            formatKwh(value) {
                return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0)) + ' kWh';
            }
        }"
        @token-sale-completed.window="tokenSale = $event.detail"
        x-cloak
    >
        <template x-if="tokenSale">
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-bold text-emerald-700">Token vendu avec succes</p>
                        <p class="mt-2 font-mono text-2xl font-extrabold tracking-wide text-gray-950" x-text="tokenSale.token"></p>
                        <p class="mt-2 text-sm text-gray-700">
                            <span x-text="tokenSale.address"></span>
                            <span x-show="tokenSale.room"> - Numéro du bien <span x-text="tokenSale.room"></span></span>
                            <span> - </span><span x-text="formatMoney(tokenSale.amount)"></span>
                            <span x-show="Number(tokenSale.operator_fee || 0) > 0"> - Frais <span x-text="formatMoney(tokenSale.operator_fee)"></span></span>
                            <span x-show="Number(tokenSale.sms_fee || 0) > 0"> - SMS <span x-text="formatMoney(tokenSale.sms_fee)"></span></span>
                            <span x-show="Number(tokenSale.operator_fee || 0) > 0 || Number(tokenSale.sms_fee || 0) > 0"> - Total <span x-text="formatMoney(tokenSale.total)"></span></span>
                            <span> - </span><span x-text="formatKwh(tokenSale.kwh)"></span>
                            <span> - </span><span x-text="tokenSale.payment_method || 'Paiement Mobile'"></span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a :href="tokenSale.receipt_url" target="_blank" class="inline-flex items-center justify-center rounded-md bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700">
                            Recu
                        </a>
                        <button type="button" @click="tokenSale = null" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-emerald-200 bg-white text-xl font-bold leading-none text-emerald-700 shadow-sm transition hover:bg-emerald-100" aria-label="Fermer le token vendu" title="Fermer">
                            &times;
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="max-w-4xl overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
         x-data="{
            rate: {{ (int) $ratePerKwh }},
            minimumAmount: 240,
            amountModulo: 120,
            operatorFeeRate: 0.03,
            smsFeeAmount: 25,
            amount: @js(old('amount', '')),
            room: @js(old('room_number', '')),
            paymentMethod: @js(old('payment_method', 'cash')),
            customerPhone: @js(old('customer_phone', '')),
            sendSms: @js((bool) old('send_sms', false)),
            smsPhone: @js(old('sms_phone', '')),
            processing: false,
            tokenSale: null,
            transaction: null,
            paymentModalOpen: false,
            paymentMessage: '',
            paymentCheckTimer: null,
            rooms: @js($rooms),
            showRooms: false,
            meterAddress: @js(old('meter_address', $addresses[0])),
            recentSaleMessage: '',
            showRecentWarning: false,
            confirmedOverride: false,
            get kwh() {
                const value = Number(this.amount || 0);
                return value > 0 ? (value / this.rate).toFixed(2) : '0.00';
            },
            get formattedAmount() {
                const value = Number(this.amount || 0);
                return value > 0 ? new Intl.NumberFormat('fr-FR').format(value) + ' FCFA' : '0 FCFA';
            },
            get operatorFee() {
                const value = Number(this.amount || 0);
                return this.paymentMethod === 'mobile_money' && value > 0 ? Math.round(value * this.operatorFeeRate) : 0;
            },
            get formattedOperatorFee() {
                return new Intl.NumberFormat('fr-FR').format(this.operatorFee) + ' FCFA';
            },
            get smsFee() {
                return this.sendSms ? this.smsFeeAmount : 0;
            },
            get formattedSmsFee() {
                return new Intl.NumberFormat('fr-FR').format(this.smsFee) + ' FCFA';
            },
            get totalToPay() {
                return Number(this.amount || 0) + this.operatorFee + this.smsFee;
            },
            get formattedTotalToPay() {
                return this.totalToPay > 0 ? new Intl.NumberFormat('fr-FR').format(this.totalToPay) + ' FCFA' : '0 FCFA';
            },
            get paymentLabel() {
                return this.paymentMethod === 'mobile_money' ? 'Paiement Mobile' : 'Especes';
            },
            get mobileOperator() {
                return this.operatorForCameroonPhone(this.customerPhone);
            },
            get mobileOperatorLabel() {
                if (this.mobileOperator === 'CM_ORANGEMONEY') {
                    return 'Orange Money';
                }

                if (this.mobileOperator === 'CM_MTNMOBILEMONEY') {
                    return 'MTN Momo';
                }

                return '';
            },
            get mobileOperatorColor() {
                if (this.mobileOperator === 'CM_ORANGEMONEY') {
                    return '#f97316';
                }

                if (this.mobileOperator === 'CM_MTNMOBILEMONEY') {
                    return '#facc15';
                }

                return 'transparent';
            },
            get mobilePhoneDigits() {
                let digits = String(this.customerPhone || '').replace(/\D/g, '');

                if (digits.length > 3 && digits.startsWith('237')) {
                    digits = digits.slice(3);
                }

                return digits;
            },
            get mobilePaymentReady() {
                return this.mobilePhoneDigits.length === 9 && Boolean(this.mobileOperator);
            },
            get smsPhoneDigits() {
                let digits = String(this.smsPhone || '').replace(/\D/g, '');

                if (digits.length > 3 && digits.startsWith('237')) {
                    digits = digits.slice(3);
                }

                return digits;
            },
            get smsReady() {
                return !this.sendSms || this.smsPhoneDigits.length === 9;
            },
            get tokenFormReady() {
                const amountValue = Number(this.amount || 0);
                const roomReady = this.exactRoomSelected();
                const amountReady = amountValue >= this.minimumAmount && this.validAmountModulo;
                const paymentReady = this.paymentMethod === 'cash' || this.mobilePaymentReady;

                return roomReady && amountReady && paymentReady && this.smsReady;
            },
            get validAmountModulo() {
                const amountValue = Number(this.amount || 0);

                return amountValue > 0 && amountValue % this.amountModulo === 0;
            },
            get amountInvalid() {
                const amountValue = Number(this.amount || 0);

                return amountValue > 0 && !this.validAmountModulo;
            },
            get amountTooLow() {
                const amountValue = Number(this.amount || 0);

                return amountValue > 0 && amountValue < this.minimumAmount;
            },
            normalize(value) {
                return String(value || '').toUpperCase().replace(/\s+/g, '').trim();
            },
            formatCameroonPhone() {
                let digits = String(this.customerPhone || '').replace(/\D/g, '');
                const hasCountryCode = digits.startsWith('237');
                const maxLength = hasCountryCode ? 12 : 9;

                digits = digits.slice(0, maxLength);
                const groups = [];

                for (let index = 0; index < digits.length; index += 3) {
                    groups.push(digits.slice(index, index + 3));
                }

                this.customerPhone = groups.join(' ');
            },
            formatSmsPhone() {
                let digits = String(this.smsPhone || '').replace(/\D/g, '');
                const hasCountryCode = digits.startsWith('237');
                const maxLength = hasCountryCode ? 12 : 9;

                digits = digits.slice(0, maxLength);
                const groups = [];

                for (let index = 0; index < digits.length; index += 3) {
                    groups.push(digits.slice(index, index + 3));
                }

                this.smsPhone = groups.join(' ');
            },
            toggleSms() {
                if (this.sendSms && !this.smsPhone) {
                    this.smsPhone = this.customerPhone;
                }
            },
            operatorForCameroonPhone(phone) {
                let digits = String(phone || '').replace(/\D/g, '');

                if (digits.length > 3 && digits.startsWith('237')) {
                    digits = digits.slice(3);
                }

                if (digits.length < 3 || !digits.startsWith('6')) {
                    return null;
                }

                const prefix = Number(digits.slice(0, 3));
                const isMtn = (prefix >= 650 && prefix <= 654) || (prefix >= 670 && prefix <= 679) || (prefix >= 680 && prefix <= 683);
                const isOrange = (prefix >= 655 && prefix <= 659) || (prefix >= 685 && prefix <= 689) || (prefix >= 690 && prefix <= 699);

                if (isMtn) {
                    return 'CM_MTNMOBILEMONEY';
                }

                if (isOrange) {
                    return 'CM_ORANGEMONEY';
                }

                return null;
            },
            exactRoomSelected() {
                return this.rooms.includes(this.normalize(this.room));
            },
            roomSuggestions() {
                const query = this.normalize(this.room);

                if (!query || this.exactRoomSelected()) {
                    return [];
                }

                return this.rooms.filter((item) => item.includes(query));
            },
            chooseRoom(value) {
                this.room = value;
                this.showRooms = false;
            },
            async submitTokenSale(event) {
                event.preventDefault();

                if (!this.tokenFormReady || this.processing) {
                    return;
                }

                this.processing = true;

                // Vérification 48h — sauf si la vendeuse a déjà confirmé l'override
                if (!this.confirmedOverride) {
                    const hasRecent = await this.check48hRecent();
                    if (hasRecent) {
                        this.processing = false;
                        return; // Modal d'avertissement affiché, on attend
                    }
                }

                // Paiement espèces : laisser Alpine re-rendre le spinner, puis soumettre
                await new Promise(resolve => setTimeout(resolve, 80));
                this.$refs.tokenForm.submit();
                return;
            },
            schedulePaymentCheck() {
                if (this.paymentCheckTimer) {
                    clearTimeout(this.paymentCheckTimer);
                }

                this.paymentCheckTimer = setTimeout(() => this.checkPayment(), 5000);
            },
            async checkPayment() {
                if (!this.transaction?.id) {
                    return;
                }

                this.processing = true;

                try {
                    const url = '{{ route('seller.payments.check', ':id') }}'.replace(':id', this.transaction.id);
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({}),
                    });
                    const data = await response.json();

                    this.transaction = data.transaction || this.transaction;
                    this.paymentMessage = data.message || this.paymentMessage;

                    if (data.status === 'success' && data.sale_id) {
                        const completedSale = {
                            token: data.token_sale?.token || 'Token genere. Voir le recu.',
                            receipt_url: data.receipt_url,
                            invoice_number: data.invoice_number,
                            address: data.token_sale?.address || '',
                            room: data.token_sale?.room || '',
                            amount: data.token_sale?.amount || 0,
                            operator_fee: data.token_sale?.operator_fee || 0,
                            sms_fee: data.token_sale?.sms_fee || 0,
                            total: data.token_sale?.total || 0,
                            kwh: data.token_sale?.kwh || 0,
                            payment_method: data.token_sale?.payment_method || this.paymentLabel,
                        };

                        this.tokenSale = completedSale;
                        this.transaction = null;
                        this.paymentModalOpen = false;
                        window.dispatchEvent(new CustomEvent('token-sale-completed', { detail: completedSale }));
                        return;
                    }

                    if (!['failed', 'expired', 'paid_action_required'].includes(data.status)) {
                        this.schedulePaymentCheck();
                    }
                } catch (error) {
                    this.paymentMessage = 'Impossible de verifier le paiement pour le moment.';
                } finally {
                    this.processing = false;
                }
            },
            // Vérifie en BD si un token a été vendu pour cette adresse + numéro du bien dans les 48h
            async check48hRecent() {
                if (!this.room || !this.meterAddress) return false;

                try {
                    const response = await fetch('{{ route('seller.tokens.check-recent') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            meter_address: this.meterAddress,
                            room_number: this.room,
                        }),
                    });
                    const data = await response.json();

                    if (data.recent) {
                        this.recentSaleMessage = data.message;
                        this.showRecentWarning = true;
                        return true;
                    }
                } catch (e) {
                    // Erreur réseau : le contrôle serveur reste obligatoire juste avant Socadel.
                }

                return false;
            },
            // Appelé quand la vendeuse confirme vouloir vendre malgré l'avertissement 48h
            async confirmAndProceed() {
                this.showRecentWarning = false;
                this.confirmedOverride = true;

                this.processing = true;
                await new Promise(resolve => setTimeout(resolve, 80));
                this.$refs.tokenForm.submit();
                return;
            },
            readablePaymentMessage(message) {
                const value = String(message || '');

                if (value === 'LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED') {
                    return 'Solde insuffisant, limite atteinte ou paiement non autorise.';
                }

                return value;
            }
         }">
        <!-- Modal avertissement 48h — vente récente détectée -->
        <div
            x-show="showRecentWarning"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-gray-950/50" aria-hidden="true"></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl">
                <div class="border-b border-amber-100 bg-amber-50 px-5 py-4">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M12 9v3m0 4h.01M10.29 3.86 1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Vente récente détectée</p>
                            <h3 class="mt-0.5 text-base font-extrabold text-gray-950">Voulez-vous vraiment vendre encore ?</h3>
                        </div>
                    </div>
                </div>
                <div class="px-5 py-5">
                    <p class="text-sm font-semibold text-gray-800" x-text="recentSaleMessage"></p>
                </div>
                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        @click="showRecentWarning = false"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50"
                    >
                        Annuler
                    </button>
                    <button
                        type="button"
                        @click="confirmAndProceed()"
                        class="inline-flex justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700"
                    >
                        Oui, vendre quand même
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal paiement Monetbil -->
        <div x-show="paymentModalOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
             role="dialog"
             aria-modal="true">
            <div class="fixed inset-0 bg-gray-950/50" aria-hidden="true"></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl">
                <div class="border-b border-rose-100 px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-rose-600">Paiement Monetbil</p>
                    <h3 class="mt-1 text-lg font-extrabold text-gray-950">Validation du paiement mobile</h3>
                </div>
                <div class="px-5 py-5">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 h-3 w-3 shrink-0 animate-pulse rounded-full bg-rose-600"></div>
                        <div class="min-w-0">
                            <p class="break-words text-sm font-semibold text-gray-800" x-text="readablePaymentMessage(paymentMessage) || 'Paiement en attente de confirmation Monetbil.'"></p>
                            <p x-show="transaction?.reference" class="mt-3 break-all rounded-md bg-rose-50 px-3 py-2 text-sm font-semibold text-gray-950">
                                Reference: <span x-text="transaction?.reference"></span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                    <button type="button" @click="paymentModalOpen = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">
                        Masquer
                    </button>
                    <button type="button" @click="checkPayment()" :disabled="processing || !transaction?.id" class="rounded-md bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm disabled:opacity-60">
                        Verifier
                    </button>
                </div>
            </div>
        </div>

        <div class="border-b border-gray-100 px-6 py-5">
            <p class="text-sm font-bold uppercase tracking-wide text-rose-600">Vente token</p>
            <p class="mt-1 text-sm text-gray-500">Saisissez le numéro du bien et le montant, le kWh est calcule automatiquement.</p>
        </div>

        <form x-ref="tokenForm" method="POST" action="{{ route('seller.tokens.store') }}" @submit="submitTokenSale($event)">
            @csrf
            <input type="hidden" name="confirmed_recent_token" :value="confirmedOverride ? '1' : '0'">

            <div class="grid gap-6 px-6 py-6 lg:grid-cols-[1fr_280px]">
                <div class="space-y-5">
                    <div>
                        <label for="meter_address" class="block text-sm font-bold text-gray-800">Adresse du compteur</label>
                        <select name="meter_address" id="meter_address" required x-model="meterAddress" class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-rose-500 focus:ring-rose-500">
                            @foreach($addresses as $address)
                                <option value="{{ $address }}" @selected(old('meter_address', $addresses[0]) === $address)>{{ $address }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="relative">
                        <label for="room_number" class="block text-sm font-bold text-gray-800">Numéro du bien</label>
                        <input
                            type="text"
                            name="room_number"
                            id="room_number"
                            required
                            x-model="room"
                            @focus="showRooms = true"
                            @input="room = normalize(room); showRooms = true"
                            @keydown.escape="showRooms = false"
                            placeholder="Ex: C1, A10"
                            autocomplete="off"
                            class="mt-1 block w-full rounded-md border-gray-300 uppercase shadow-sm focus:border-rose-500 focus:ring-rose-500"
                        >
                        <div x-show="showRooms && roomSuggestions().length > 0" x-cloak class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                            <template x-for="suggestion in roomSuggestions()" :key="suggestion">
                                <button type="button" @mousedown.prevent="chooseRoom(suggestion)" class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-rose-50">
                                    <span class="font-semibold text-gray-900" x-text="suggestion"></span>
                                    <span class="text-xs text-gray-400">Numéro du bien</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label for="amount" class="block text-sm font-bold text-gray-800">Montant vendu</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input
                                type="number"
                                name="amount"
                                id="amount"
                                min="240"
                                step="120"
                                required
                                x-model="amount"
                                placeholder="Ex: 1200"
                                class="block w-full rounded-l-md border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                            >
                            <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm font-bold text-gray-700">FCFA</span>
                        </div>
                        <p x-show="amountTooLow" x-cloak class="mt-1 text-xs font-semibold text-red-600">
                            Montant minimum : 240 FCFA.
                        </p>
                        <p x-show="amountInvalid" x-cloak class="mt-1 text-xs font-semibold text-red-600">
                            montant invalide. Veuillez saisir un multiple de 120.
                        </p>
                    </div>

                    <div class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3">
                        <label for="send_sms" class="flex items-center gap-3 text-sm font-bold text-gray-800">
                            <input
                                type="checkbox"
                                name="send_sms"
                                id="send_sms"
                                value="1"
                                x-model="sendSms"
                                @change="toggleSms()"
                                class="rounded border-gray-300 text-rose-600 shadow-sm focus:ring-rose-500"
                            >
                            <span>SMS</span>
                        </label>

                        <div x-show="sendSms" x-cloak class="mt-3">
                            <label for="sms_phone" class="block text-sm font-bold text-gray-800">Numero SMS</label>
                            <input
                                type="tel"
                                name="sms_phone"
                                id="sms_phone"
                                x-model="smsPhone"
                                @input="formatSmsPhone()"
                                :required="sendSms"
                                maxlength="15"
                                inputmode="numeric"
                                placeholder="Ex: 6XX XXX XXX"
                                autocomplete="tel"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                            >
                            <p x-show="sendSms && smsPhone && !smsReady" class="mt-1 text-xs text-red-600">
                                Numero SMS invalide - 9 chiffres requis.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="payment_method" class="block text-sm font-bold text-gray-800">Mode de paiement</label>
                            <select
                                name="payment_method"
                                id="payment_method"
                                required
                                x-model="paymentMethod"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-rose-500 focus:ring-rose-500"
                            >
                                <option value="cash">Especes</option>
                                <option value="mobile_money">Paiement Mobile</option>
                            </select>
                        </div>

                        <div x-show="paymentMethod === 'mobile_money'" x-cloak>
                            <label for="customer_phone" class="block text-sm font-bold text-gray-800">Numero paiement mobile</label>
                            <div
                                class="mt-1 flex min-h-11 overflow-hidden rounded-md border bg-white shadow-sm focus-within:ring-1"
                                :class="mobileOperator && !mobilePaymentReady ? 'border-red-500 focus-within:border-red-500 focus-within:ring-red-500' : 'border-gray-300 focus-within:border-rose-500 focus-within:ring-rose-500'"
                            >
                                <div
                                    x-show="mobileOperator"
                                    class="flex shrink-0 items-center gap-2 border-r border-gray-200 bg-gray-50 px-3 text-sm font-semibold text-gray-900"
                                >
                                    <span
                                        class="h-3.5 w-3.5 rounded-full ring-1 ring-gray-900/20"
                                        :style="'background-color: ' + mobileOperatorColor"
                                        aria-hidden="true"
                                    ></span>
                                    <span x-text="mobileOperatorLabel"></span>
                                </div>
                                <input
                                    type="tel"
                                    name="customer_phone"
                                    id="customer_phone"
                                    x-model="customerPhone"
                                    @input="formatCameroonPhone()"
                                    :required="paymentMethod === 'mobile_money'"
                                    maxlength="15"
                                    inputmode="numeric"
                                    placeholder="Ex: 6XX XXX XXX"
                                    autocomplete="tel"
                                    class="min-w-0 flex-1 border-0 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:ring-0"
                                >
                            </div>
                            <p x-show="mobileOperator && !mobilePaymentReady" class="mt-1 text-xs text-red-600">
                                Numero invalide - 9 chiffres requis.
                            </p>
                        </div>
                    </div>
                </div>

                <aside class="rounded-lg border border-rose-100 bg-rose-50/70 p-5">
                    <p class="text-sm font-bold text-gray-900">Resume</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-600">Adresse</dt>
                            <dd class="font-bold text-gray-950">{{ $addresses[0] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-600">Numéro du bien</dt>
                            <dd class="font-bold text-gray-950" x-text="room || '-'"></dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-600">Tarif</dt>
                            <dd class="font-bold text-gray-950">{{ number_format($ratePerKwh, 0, ',', ' ') }} FCFA / kWh</dd>
                        </div>
                        <div class="border-t border-rose-200 pt-3">
                            <dt class="text-gray-600">Montant</dt>
                            <dd class="mt-1 text-xl font-extrabold text-gray-950" x-text="formattedAmount"></dd>
                        </div>
                        <div x-show="paymentMethod === 'mobile_money'" x-cloak class="flex items-center justify-between gap-4">
                            <dt class="text-gray-600">Frais operateur (3%)</dt>
                            <dd class="font-bold text-gray-950" x-text="formattedOperatorFee"></dd>
                        </div>
                        <div x-show="sendSms" x-cloak class="flex items-center justify-between gap-4">
                            <dt class="text-gray-600">SMS</dt>
                            <dd class="font-bold text-gray-950" x-text="formattedSmsFee"></dd>
                        </div>
                        <div x-show="paymentMethod === 'mobile_money' || sendSms" x-cloak class="border-t border-rose-200 pt-3">
                            <dt class="text-gray-600">Total a payer</dt>
                            <dd class="mt-1 text-xl font-extrabold text-rose-600" x-text="formattedTotalToPay"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-600">Paiement</dt>
                            <dd class="mt-1 text-base font-extrabold text-gray-950" x-text="paymentLabel"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-600">Kilowatts calcules</dt>
                            <dd class="mt-1 text-2xl font-extrabold text-rose-600"><span x-text="kwh"></span> kWh</dd>
                        </div>
                    </dl>
                </aside>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
                <a href="{{ route('seller.dashboard') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    Annuler
                </a>
                <button
                    type="submit"
                    :disabled="!tokenFormReady || processing"
                    class="inline-flex items-center justify-center rounded-md bg-rose-600 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <svg x-show="processing" class="-ml-1 mr-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="processing ? 'Traitement...' : 'Vendre'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
