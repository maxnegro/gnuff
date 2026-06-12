import { mount } from '@vue/test-utils';
import { ref, nextTick } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const cropperState = {
    canvas: ref(null),
    ctx: ref(null),
    image: ref({ width: 120, height: 120 }),
    zoom: ref(1),
    panX: ref(0),
    panY: ref(0),
    isLoading: ref(false),
    error: ref(null),
    maxZoom: ref(3),
    minZoom: ref(0.5),
    initCanvas: vi.fn(() => true),
    draw: vi.fn(),
    setZoom: vi.fn(),
    setPan: vi.fn(),
    reset: vi.fn(),
    getCroppedImage: vi.fn(() => 'data:image/jpeg;base64,stub'),
    getImageDimensions: vi.fn(() => ({ width: 120, height: 120 })),
    loadImageFromBase64: vi.fn(),
    loadImageFromFile: vi.fn(),
};

vi.mock('@/composables/useImageCropper', () => ({
    useImageCropper: () => cropperState,
}));

import ImageCropModal from '@/Components/ImageCropModal.vue';

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('ImageCropModal', () => {
    beforeEach(() => {
        cropperState.initCanvas.mockClear();
        cropperState.draw.mockClear();
        cropperState.reset.mockClear();
        cropperState.image.value = { width: 120, height: 120 };
        cropperState.isLoading.value = false;
        cropperState.error.value = null;
    });

    it('inizializza la canvas quando la modale si apre dopo il caricamento dell immagine', async () => {
        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: false,
            },
        });

        expect(cropperState.initCanvas).not.toHaveBeenCalled();

        await wrapper.setProps({ isOpen: true });
        await flushPromises();
        await nextTick();
        await flushPromises();
        await nextTick();

        expect(cropperState.initCanvas).toHaveBeenCalled();
        expect(cropperState.draw).toHaveBeenCalled();
        expect(wrapper.find('canvas').exists()).toBe(true);
    });
});