<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import {useToast} from 'vue-toastification';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Two Factor settings',
        href: '/settings/2fa',
    },
];

interface TwoFactorPageProps{
    QRImage?: string;
    secretKey?: string;
    enabled_2fa?: boolean;
}

const toast = useToast();
const passcodeInput = ref<HTMLInputElement | null>(null);

const page = usePage();
const QRImage = ref<string>((page.props as TwoFactorPageProps).QRImage || '');
const secretKey = ref<string>((page.props as TwoFactorPageProps).secretKey || '');
const enabled_2fa = ref<boolean>((page.props as TwoFactorPageProps).enabled_2fa || false);

const form = useForm({
    passcode: '',
    secret: secretKey.value,
    enabled_2fa: enabled_2fa.value
});

const enable2FA = () => {
    form.post(route('settings.2fa.enable'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            toast('2FA authentication is enabled');
        },
        onError: (errors: any) => {
            if (errors.passcode) {
                form.reset('passcode');
                if (passcodeInput.value instanceof HTMLInputElement) {
                    passcodeInput.value.focus();
                }
            }
        },
    });
};

const disabled2FA = () => {
    form.enabled_2fa = !form.enabled_2fa;

    if(!form.enabled_2fa){
        form.put(route('settings.2fa.disable'),{
            preserveScroll: true,
            onSuccess: () => {
                toast('2FA authentication is disabled');
            },
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Two Factor Authentication" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Enable Two Factor Authentication" description="Add an extra layer of security to your account by enabling two-factor authentication." />

                <div class="flex gap-2 justify-start mb-4">
                    <input ref="checkbox" id="checkbox" type="checkbox" class=" rounded p-4" @change="disabled2FA" :checked="form.enabled_2fa" />
                    <label for="checkbox">Enable 2FA</label>
                </div>

                <form @submit.prevent="enable2FA" class="space-y-6" v-if="form.enabled_2fa">

                    <div class="flex justify-start mb-4">
                        <div class="border p-2 rounded bg-gray-100">
                            <div v-html="QRImage"></div>
                        </div>
                    </div>

                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-200">
                        <strong>Backup Key:</strong> {{ secretKey }} <br>
                        <span class="text-gray-500">(Keep this key safe in case you lose your device.)</span>
                    </p>

                    <div class="grid gap-2">
                        <Label for="passcodeInput">Enter OTP from Authenticator App</Label>
                        <Input
                            id="passcodeInput"
                            ref="passcodeInput"
                            v-model="form.passcode"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Passcode"
                        />
                        <InputError :message="form.errors.passcode" />
                    </div>

                    

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save password</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
