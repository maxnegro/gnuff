import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import ProductRatingModal from '@/Components/ProductRatingModal.vue';
import { axiosMock, resetTestState } from '@/__tests__/setup.js';

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('ProductRatingModal', () => {
    beforeEach(() => {
        resetTestState();
    });

    it('recupera il prodotto da EAN e passa allo step dati', async () => {
        axiosMock.get.mockResolvedValue({
            data: {
                product: {
                    name: 'Yogurt',
                    image_url: 'https://example.test/yogurt.jpg',
                },
                rating: 'gnuf',
            },
        });

        const wrapper = mount(ProductRatingModal, {
            props: {
                modelValue: true,
            },
        });

        await wrapper.get('input[placeholder="EAN (barcode)"]').setValue('800123');
        await wrapper.get('form').trigger('submit.prevent');
        await flushPromises();

        expect(axiosMock.get).toHaveBeenCalledWith('/product/800123');
        expect(wrapper.text()).toContain('Yogurt');
        expect(wrapper.text()).toContain('EAN: 800123');
        expect(wrapper.find('select').element.value).toBe('gnuf');
    });

    it('salva la valutazione ed emette gli eventi di chiusura', async () => {
        axiosMock.put.mockResolvedValue({ data: {} });
        axiosMock.post.mockResolvedValue({ data: {} });

        const wrapper = mount(ProductRatingModal, {
            props: {
                modelValue: true,
                initialStep: 'dati',
                initialForm: {
                    barcode: '800123',
                    name: 'Yogurt',
                    image_url: 'https://example.test/yogurt.jpg',
                    rating: 'ok',
                },
            },
        });

        await flushPromises();
        await wrapper.get('select').setValue('gnuf');
        await wrapper.get('form').trigger('submit.prevent');
        await flushPromises();

        expect(axiosMock.put).toHaveBeenCalledWith('/product/800123', {
            name: 'Yogurt',
            image_url: 'https://example.test/yogurt.jpg',
        });
        expect(axiosMock.post).toHaveBeenCalledWith('/rate', {
            barcode: '800123',
            value: 'gnuf',
        });
        expect(wrapper.emitted().saved).toBeTruthy();
        expect(wrapper.emitted()['update:modelValue']).toContainEqual([false]);
    });
});