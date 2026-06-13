import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ProductRatingModal from '@/Components/ProductRatingModal.vue';
import { axiosMock, resetTestState } from '@/__tests__/setup.js';

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

vi.mock('@/composables/useImageCropper', () => ({
    useImageCropper: () => ({
        image: { value: { width: 400, height: 300 } },
        zoom: { value: 1 },
        panX: { value: 0 },
        panY: { value: 0 },
        isLoading: { value: false },
        error: { value: null },
        maxZoom: { value: 3 },
        minZoom: { value: 0.5 },
        initCanvas: vi.fn(() => true),
        draw: vi.fn(),
        setZoom: vi.fn(),
        setPan: vi.fn(),
        reset: vi.fn(),
        getCroppedImage: vi.fn(() => 'data:image/jpeg;base64,croppedimage'),
        getImageDimensions: vi.fn(() => ({ width: 400, height: 300 })),
        loadImageFromBase64: vi.fn().mockResolvedValue(undefined),
        loadImageFromFile: vi.fn().mockResolvedValue(undefined),
    }),
}));

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

    it('mostra il pulsante Rimuovi valutazione quando ratingId è presente', async () => {
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
                ratingId: 42,
            },
        });

        await flushPromises();
        const removeButton = wrapper.find('[data-test="remove-rating"]');
        expect(removeButton.exists()).toBe(true);
        expect(removeButton.text()).toContain('Rimuovi valutazione');
    });

    it('elimina la valutazione quando clicca il pulsante Rimuovi', async () => {
        axiosMock.delete.mockResolvedValue({ data: { message: 'Valutazione eliminata' } });

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
                ratingId: 42,
            },
        });

        await flushPromises();
        await wrapper.get('[data-test="remove-rating"]').trigger('click');
        await flushPromises();

        expect(axiosMock.delete).toHaveBeenCalledWith('/api/rate/42');
        expect(wrapper.emitted().saved).toBeTruthy();
        expect(wrapper.emitted()['update:modelValue']).toContainEqual([false]);
    });

    it('mostra il pulsante Cambia quando c è un immagine', async () => {
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
        const changeBtn = wrapper.find('button');
        const cambiaBtns = wrapper.findAll('button').filter((b) => b.text().includes('Cambia'));

        expect(cambiaBtns.length).toBeGreaterThan(0);
    });

    it('mostra i pulsanti File e Fotocamera per upload immagine', async () => {
        const wrapper = mount(ProductRatingModal, {
            props: {
                modelValue: true,
                initialStep: 'dati',
                initialForm: {
                    barcode: '800123',
                    name: 'Yogurt',
                    image_url: '',
                    rating: 'ok',
                },
            },
        });

        await flushPromises();
        // Click "Cambia Immagine" button to show upload controls
        const cambiaBtn = wrapper.findAll('button').find((b) => b.text().includes('Cambia'));
        if (cambiaBtn) {
            await cambiaBtn.trigger('click');
            await flushPromises();
        }

        // Check that file and camera inputs exist
        const fileInput = wrapper.find('input[type="file"]');
        expect(fileInput.exists()).toBe(true);
    });

    it('gestisce l upload immagine con Base64', async () => {
        axiosMock.post.mockResolvedValue({
            data: {
                success: true,
                image_url: '/storage/products/ab/cd/800123-image.jpg',
            },
        });

        const wrapper = mount(ProductRatingModal, {
            props: {
                modelValue: true,
                initialStep: 'dati',
                initialForm: {
                    barcode: '800123',
                    name: 'Yogurt',
                    image_url: '',
                    rating: 'ok',
                },
            },
        });

        await flushPromises();

        // Simulate cropped image confirmation
        await wrapper.vm.handleImageCropConfirm('data:image/jpeg;base64,croppeddata');
        await flushPromises();

        expect(axiosMock.post).toHaveBeenCalledWith('/product/800123/image', {
            image_base64: 'data:image/jpeg;base64,croppeddata',
        });
    });

    it('gestisce errori durante l upload immagine', async () => {
        axiosMock.post.mockRejectedValue({
            response: {
                data: { message: 'Errore durante l upload dell immagine' },
            },
        });

        const wrapper = mount(ProductRatingModal, {
            props: {
                modelValue: true,
                initialStep: 'dati',
                initialForm: {
                    barcode: '800123',
                    name: 'Yogurt',
                    image_url: '',
                    rating: 'ok',
                },
            },
        });

        await flushPromises();

        await wrapper.vm.handleImageCropConfirm('data:image/jpeg;base64,croppeddata');
        await flushPromises();

        expect(wrapper.vm.manualFormError).toContain('Errore');
    });
});