<x-layout :title="__('setup.title')" robots="noindex">
    <div class="card mx-auto max-w-[440px] p-6 shadow-card sm:p-8">
        <x-icon name="lock" :size="24" class="text-navy-700" />
        <h1 class="mt-3 text-2xl leading-8 font-bold text-navy-900">{{ __('setup.title') }}</h1>
        <p class="mt-2 text-ink-muted">{{ __('setup.login_lead') }}</p>

        <form method="POST" action="{{ route('setup.login') }}" class="mt-6">
            @csrf
            <label for="token" class="block font-semibold text-navy-900">{{ __('setup.token') }}</label>
            <input
                type="password"
                id="token"
                name="token"
                required
                autocomplete="off"
                @error('token') aria-invalid="true" aria-describedby="token-error" @enderror
                class="mt-2 h-12 w-full rounded-btn border border-line bg-white px-4 focus:border-navy-700"
            >
            @error('token')
                <p id="token-error" class="mt-2 text-danger-fg" role="alert">{{ $message }}</p>
            @enderror
            <button type="submit" class="btn-primary mt-4 w-full">{{ __('setup.login_submit') }}</button>
        </form>
    </div>
</x-layout>
