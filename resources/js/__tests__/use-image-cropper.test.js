import { describe, expect, it, vi, beforeEach } from 'vitest';

// Mock canvas and context for jsdom
const mockContext = {
    clearRect: vi.fn(),
    fillRect: vi.fn(),
    drawImage: vi.fn(),
    strokeRect: vi.fn(),
    getImageData: vi.fn(),
    putImageData: vi.fn(),
};

const mockCanvas = {
    width: 400,
    height: 400,
    getContext: vi.fn(() => mockContext),
};

beforeEach(() => {
    vi.clearAllMocks();
    mockCanvas.getContext.mockReturnValue(mockContext);
});

vi.mock('@/utils/imageConverter', () => ({
    fileToDataUri: vi.fn((file) => Promise.resolve('data:image/jpeg;base64,mockdata')),
}));

describe('useImageCropper', () => {
    it('should initialize canvas with correct dimensions', async () => {
        const { useImageCropper } = await import('@/composables/useImageCropper');

        const cropper = useImageCropper(400, 400);

        const result = cropper.initCanvas(mockCanvas);

        expect(result).toBe(true);
        expect(mockCanvas.width).toBe(400);
        expect(mockCanvas.height).toBe(400);
        expect(cropper.ctx.value).toBeDefined();
    });

    it('should return error when canvas context fails', async () => {
        const { useImageCropper } = await import('@/composables/useImageCropper');

        const cropper = useImageCropper(400, 400);

        mockCanvas.getContext.mockReturnValue(null);
        const result = cropper.initCanvas(mockCanvas);

        expect(result).toBe(false);
        expect(cropper.error.value).toBe('Failed to initialize canvas context');
    });

    it('should set zoom within min/max bounds', async () => {
        const { useImageCropper } = await import('@/composables/useImageCropper');

        const cropper = useImageCropper(400, 400);

        cropper.setZoom(0.2); // Below min
        expect(cropper.zoom.value).toBe(0.5); // Should clamp to min

        cropper.setZoom(5); // Above max
        expect(cropper.zoom.value).toBe(3); // Should clamp to max

        cropper.setZoom(2); // Within bounds
        expect(cropper.zoom.value).toBe(2);
    });

    it('should reset zoom and pan to defaults', async () => {
        const { useImageCropper } = await import('@/composables/useImageCropper');

        const cropper = useImageCropper(400, 400);

        cropper.setZoom(2.5);
        cropper.setPan(100, 50);

        cropper.reset();

        expect(cropper.zoom.value).toBe(1);
        expect(cropper.panX.value).toBe(0);
        expect(cropper.panY.value).toBe(0);
    });

    it('should return error when cropping without canvas context', async () => {
        const { useImageCropper } = await import('@/composables/useImageCropper');

        const cropper = useImageCropper(400, 400);

        const result = cropper.getCroppedImage('jpeg', 0.85);
        expect(result).toBeNull();
    });

    it('should get image dimensions', async () => {
        const { useImageCropper } = await import('@/composables/useImageCropper');

        const cropper = useImageCropper(400, 400);

        cropper.image.value = { width: 800, height: 600 };
        const dims = cropper.getImageDimensions();

        expect(dims.width).toBe(800);
        expect(dims.height).toBe(600);
    });

    it('should return zero dimensions when no image', async () => {
        const { useImageCropper } = await import('@/composables/useImageCropper');

        const cropper = useImageCropper(400, 400);
        const dims = cropper.getImageDimensions();

        expect(dims.width).toBe(0);
        expect(dims.height).toBe(0);
    });
});

describe('Image File Validation', () => {
    it('should validate JPEG and PNG file types', async () => {
        const { validateImageFile } = await import('@/utils/imageFileValidation');

        const jpegFile = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
        const pngFile = new File(['content'], 'test.png', { type: 'image/png' });

        expect(validateImageFile(jpegFile).isValid).toBe(true);
        expect(validateImageFile(pngFile).isValid).toBe(true);
    });

    it('should reject unsupported formats like GIF', async () => {
        const { validateImageFile } = await import('@/utils/imageFileValidation');

        const gifFile = new File(['content'], 'test.gif', { type: 'image/gif' });

        const result = validateImageFile(gifFile);
        expect(result.isValid).toBe(false);
        expect(result.error).toContain('non supportato');
    });

    it('should reject files exceeding size limit', async () => {
        const { validateImageFile } = await import('@/utils/imageFileValidation');

        const largeFile = new File(['x'.repeat(7 * 1024 * 1024)], 'large.jpg', {
            type: 'image/jpeg',
        });

        const result = validateImageFile(largeFile, 5 * 1024 * 1024, ['image/jpeg', 'image/png', 'image/webp']);
        expect(result.isValid).toBe(false);
        expect(result.error).toContain('dimensione massima');
    });

    it('should accept WebP format', async () => {
        const { validateImageFile } = await import('@/utils/imageFileValidation');

        const webpFile = new File(['content'], 'test.webp', { type: 'image/webp' });

        expect(validateImageFile(webpFile).isValid).toBe(true);
    });
});

describe('Image Converter', () => {
    it('should convert file to data URI', async () => {
        const { fileToDataUri } = await import('@/utils/imageConverter');

        const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' });
        const result = await fileToDataUri(file);

        expect(result).toContain('data:image/jpeg;base64,');
    });
});