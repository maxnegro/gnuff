import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import BarcodeScanner from '@/Components/BarcodeScanner.vue';

vi.mock('@teckel/vue-barcode-reader', () => ({
    StreamBarcodeReader: defineComponent({
        name: 'StreamBarcodeReaderStub',
        emits: ['decode', 'loaded'],
        template: `<button data-test="stream-reader" @click="$emit('decode', '801234567890')">scan</button>`,
    }),
}));

describe('BarcodeScanner', () => {
    it('emette il codice scansionato verso il parent', async () => {
        const wrapper = mount(BarcodeScanner);

        await wrapper.get('[data-test="stream-reader"]').trigger('click');

        expect(wrapper.emitted().scanned).toEqual([['801234567890']]);
    });
});