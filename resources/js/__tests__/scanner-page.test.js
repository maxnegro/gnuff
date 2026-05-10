import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ScannerPage from '@/Pages/Scanner.vue';
import { axiosMock, resetTestState } from '@/__tests__/setup.js';

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

vi.mock('@teckel/vue-barcode-reader', () => ({
    StreamBarcodeReader: defineComponent({
        name: 'StreamBarcodeReaderStub',
        props: {
            torch: {
                type: Boolean,
                default: false,
            },
        },
        emits: ['result', 'error'],
        template: `
            <div>
                <button data-test="emit-valid" @click="$emit('result', { format: 7, text: '801234567890' })">valid</button>
                <button data-test="emit-invalid" @click="$emit('result', { format: 1, text: 'ignore-me' })">invalid</button>
                <span data-test="torch-state">{{ torch ? 'on' : 'off' }}</span>
            </div>
        `,
    }),
}));

const ProductRatingModalStub = defineComponent({
    name: 'ProductRatingModalStub',
    props: {
        modelValue: Boolean,
        initialStep: String,
        initialForm: {
            type: Object,
            default: () => ({}),
        },
        ratingId: {
            type: Number,
            default: null,
        },
    },
    emits: ['saved', 'update:modelValue'],
    template: `
        <div v-if="modelValue" data-test="product-modal">
            <span data-test="modal-step">{{ initialStep }}</span>
            <span data-test="modal-barcode">{{ initialForm.barcode }}</span>
            <span data-test="modal-name">{{ initialForm.name }}</span>
            <span data-test="modal-rating">{{ initialForm.rating }}</span>
            <span data-test="modal-rating-id">{{ ratingId }}</span>
            <button data-test="modal-saved" @click="$emit('saved')">save</button>
        </div>
    `,
});

describe('Scanner page', () => {
    beforeEach(() => {
        resetTestState();
    });

    it('attiva torcia e pausa scanner dai controlli UI', async () => {
        const wrapper = mount(ScannerPage, {
            global: {
                stubs: {
                    ProductRatingModal: ProductRatingModalStub,
                },
            },
        });

        expect(wrapper.get('[data-test="torch-state"]').text()).toBe('off');
        expect(wrapper.text()).toContain('⏸️ Pausa');

        await wrapper.get('button.btn').trigger('click');
        expect(wrapper.get('[data-test="torch-state"]').text()).toBe('on');

        await wrapper.findAll('button.btn')[1].trigger('click');
        expect(wrapper.text()).toContain('▶️ Riprendi');
    });

    it('apre la modale con i dati prodotto quando legge un EAN supportato', async () => {
        axiosMock.get.mockResolvedValue({
            data: {
                product: {
                    name: 'Biscotti',
                    image_url: 'https://example.test/biscotti.jpg',
                },
                rating: 'ok',
                rating_id: 42,
            },
        });

        const wrapper = mount(ScannerPage, {
            global: {
                stubs: {
                    ProductRatingModal: ProductRatingModalStub,
                },
            },
        });

        await wrapper.get('[data-test="emit-valid"]').trigger('click');
        await flushPromises();

        expect(axiosMock.get).toHaveBeenCalledWith('/product/801234567890');
        expect(wrapper.get('[data-test="product-modal"]').exists()).toBe(true);
        expect(wrapper.get('[data-test="modal-step"]').text()).toBe('dati');
        expect(wrapper.get('[data-test="modal-barcode"]').text()).toBe('801234567890');
        expect(wrapper.get('[data-test="modal-name"]').text()).toBe('Biscotti');
        expect(wrapper.get('[data-test="modal-rating"]').text()).toBe('ok');
        expect(wrapper.get('[data-test="modal-rating-id"]').text()).toBe('42');
        expect(wrapper.text()).toContain('▶️ Riprendi');
    });
});