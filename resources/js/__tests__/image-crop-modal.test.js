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
        cropperState.getCroppedImage.mockClear();
        cropperState.setZoom.mockClear();
        cropperState.setPan.mockClear();
        cropperState.image.value = { width: 120, height: 120 };
        cropperState.isLoading.value = false;
        cropperState.error.value = null;
        cropperState.zoom.value = 1;
        cropperState.panX.value = 0;
        cropperState.panY.value = 0;
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

    it('chiude la modale ed emette close quando si clicca su Annulla', async () => {
        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: true,
            },
        });

        const buttons = wrapper.findAll('button');
        let annullaBtn;
        for (let i = 0; i < buttons.length; i++) {
            if (buttons[i].text().includes('Annulla')) {
                annullaBtn = buttons[i];
                break;
            }
        }

        expect(annullaBtn).toBeDefined();
        await annullaBtn.trigger('click');

        expect(wrapper.emitted('close')).toBeTruthy();
        expect(cropperState.reset).toHaveBeenCalled();
        expect(cropperState.image.value).toBeNull();
    });

    it('emette confirm con l immagine base64 quando si clicca su Conferma', async () => {
        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: true,
            },
        });

        const buttons = wrapper.findAll('button');
        let confermaBtn;
        for (let i = 0; i < buttons.length; i++) {
            if (buttons[i].text().includes('Conferma')) {
                confermaBtn = buttons[i];
                break;
            }
        }

        expect(confermaBtn).toBeDefined();
        await confermaBtn.trigger('click');

        expect(cropperState.getCroppedImage).toHaveBeenCalledWith('jpeg', 0.85);
        expect(wrapper.emitted('confirm')).toBeTruthy();
        expect(wrapper.emitted('confirm')[0][0]).toBe('data:image/jpeg;base64,stub');
    });

    it('chiama reset del cropper quando si clicca sul pulsante Reset', async () => {
        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: true,
            },
        });

        const buttons = wrapper.findAll('button');
        let resetBtn;
        for (let i = 0; i < buttons.length; i++) {
            if (buttons[i].text().includes('Reset')) {
                resetBtn = buttons[i];
                break;
            }
        }

        expect(resetBtn).toBeDefined();
        await resetBtn.trigger('click');

        expect(cropperState.reset).toHaveBeenCalled();
    });

    it('mostra lo stato di loading quando isLoading è true', async () => {
        cropperState.isLoading.value = true;
        cropperState.image.value = null;

        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: true,
            },
        });

        // Loading div should be visible
        expect(wrapper.text()).toContain('Caricamento immagine');
        const loadingDiv = wrapper.find('.animate-spin');
        expect(loadingDiv.exists()).toBe(true);
    });

    it('mostra lo stato di errore quando error è impostato', async () => {
        cropperState.error.value = 'Failed to load image';

        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: true,
            },
        });

        expect(wrapper.text()).toContain('Failed to load image');
    });

    it('gestisce il cambio dello zoom tramite slider', async () => {
        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: true,
            },
        });

        await nextTick();

        const slider = wrapper.find('input[type="range"]');
        expect(slider.exists()).toBe(true);

        // Simulate input event with proper event target mock
        const mockEvent = { target: { value: '2' } };
        wrapper.vm.handleZoomChange(mockEvent);

        expect(cropperState.setZoom).toHaveBeenCalledWith(2);
    });

    it('mostra la percentuale dello zoom', async () => {
        cropperState.zoom.value = 2;

        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: true,
            },
        });

        expect(wrapper.text()).toContain('200%');
    });

    it('gestisce il double-click per resettare zoom e pan', async () => {
        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: true,
            },
        });

        const canvas = wrapper.find('canvas');
        await canvas.trigger('dblclick');

        expect(cropperState.reset).toHaveBeenCalled();
    });

    it('configura e deconfigura i listener touch su canvas', async () => {
        const addEventListenerSpy = vi.spyOn(EventTarget.prototype, 'addEventListener');

        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: true,
            },
        });

        await flushPromises();
        await nextTick();
        await flushPromises();

        // Verify touch listeners are added
        expect(addEventListenerSpy).toHaveBeenCalledWith('touchstart', expect.any(Function), { passive: true });
        expect(addEventListenerSpy).toHaveBeenCalledWith('touchmove', expect.any(Function), { passive: false });
        expect(addEventListenerSpy).toHaveBeenCalledWith('touchend', expect.any(Function), { passive: true });
    });

    it('espone i metodi per caricare immagini', async () => {
        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: false,
            },
        });

        expect(wrapper.vm.loadImageFromBase64).toBeDefined();
        expect(wrapper.vm.loadImageFromFile).toBeDefined();
    });

    it('gestisce il pan tramite mousedown su desktop', async () => {
        const wrapper = mount(ImageCropModal, {
            props: {
                isOpen: true,
            },
        });

        await nextTick();
        await flushPromises();

        const canvas = wrapper.find('canvas');

        // Simulate mousedown - this sets up dragging state
        await canvas.trigger('mousedown', { clientX: 100, clientY: 100 });

        // The setPan should be called during the mousemove sequence
        wrapper.vm.onMousemove({ clientX: 150, clientY: 150 });

        expect(cropperState.setPan).toHaveBeenCalled();
    });
});