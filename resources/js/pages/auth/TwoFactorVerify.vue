<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref, nextTick } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    passcode: '',
});

const passcodeInputs = ref<Array<HTMLInputElement>>([]);
const passcodeDigits = ref(['', '', '', '', '', '']);

const handleInput = async (index: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value.replace(/\D/g, ''); // Only allow digits
    
    if (value.length > 1) {
        // Handle pasted content
        const digits = value.slice(0, 6).split('');
        for (let i = 0; i < 6; i++) {
            passcodeDigits.value[i] = digits[i] || '';
        }
        updatePasscode();
        
        // Focus on the last filled input or next empty one
        const nextIndex = Math.min(digits.length, 5);
        await nextTick();
        passcodeInputs.value[nextIndex]?.focus();
    } else {
        passcodeDigits.value[index] = value;
        updatePasscode();
        
        // Move to next input if digit entered
        if (value && index < 5) {
            await nextTick();
            passcodeInputs.value[index + 1]?.focus();
        }
    }
};

const handleKeydown = async (index: number, event: KeyboardEvent) => {
    if (event.key === 'Backspace' && !passcodeDigits.value[index] && index > 0) {
        // Move to previous input on backspace if current is empty
        await nextTick();
        passcodeInputs.value[index - 1]?.focus();
    } else if (event.key === 'ArrowLeft' && index > 0) {
        await nextTick();
        passcodeInputs.value[index - 1]?.focus();
    } else if (event.key === 'ArrowRight' && index < 5) {
        await nextTick();
        passcodeInputs.value[index + 1]?.focus();
    }
};

const updatePasscode = () => {
    form.passcode = passcodeDigits.value.join('');
};

const submit = () => {
    form.post(route('2fa.verify'), {
        onFinish: () => {
            form.reset('passcode');
            passcodeDigits.value = ['', '', '', '', '', ''];
        },
    });
};
</script>

<template>
    <AuthBase title="2FA Authentication" description="Enter your 6-digit passcode below to authenticate">
        <Head title="2FA Authentication" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-4">
                    <Label class="text-center">Enter 6-Digit Passcode</Label>
                    
                    <!-- 6-Digit Passcode Input -->
                    <div class="flex justify-center gap-3">
                        <input
                            v-for="(digit, index) in passcodeDigits"
                            :key="index"
                            :ref="(el) => { if (el) passcodeInputs[index] = el as HTMLInputElement }"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            :value="digit"
                            @input="handleInput(index, $event)"
                            @keydown="handleKeydown(index, $event)"
                            class="w-12 h-12 text-center text-lg font-semibold border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-all duration-200"
                            :class="{
                                'border-red-500 focus:border-red-500 focus:ring-red-200': form.errors.passcode,
                                'bg-gray-50 dark:bg-neutral-600': digit
                            }"
                            :tabindex="index + 1"
                            autocomplete="one-time-code"
                        />
                    </div>
                    
                    <InputError :message="form.errors.passcode" class="text-center" />
                </div>

                <Button 
                    type="submit" 
                    class="mt-4 w-full" 
                    :tabindex="7" 
                    :disabled="form.processing || form.passcode.length !== 6"
                >
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                    {{ form.processing ? 'Verifying...' : 'Verify Passcode' }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                <TextLink v-if="canResetPassword" :href="route('password.request')" :tabindex="8">
                    Having trouble? Reset your password
                </TextLink>
            </div>
        </form>
    </AuthBase>
</template>
