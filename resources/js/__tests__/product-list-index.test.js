import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ProductListIndex from '@/Pages/ProductList/Index.vue';
import { inertiaRouter, resetTestState, setPageProps } from '@/__tests__/setup.js';

describe('ProductList/Index', () => {
    beforeEach(() => {
        resetTestState();
        setPageProps({
            user: { id: 10, name: 'Mario' },
            auth: { user: { id: 10, name: 'Mario', email: 'mario@example.com' } },
            owned: [
                {
                    id: 1,
                    name: 'Dispensa',
                    owner_id: 10,
                    users: [{ id: 10, name: 'Mario' }],
                    products: [{ id: 100, name: 'Pasta' }],
                },
            ],
            shared: [
                {
                    id: 1,
                    name: 'Dispensa',
                    owner_id: 10,
                    users: [{ id: 10, name: 'Mario' }],
                    products: [{ id: 100, name: 'Pasta' }],
                },
            ],
            invitations: [
                { id: 5, list_name: 'Casa', owner_name: 'Giulia' },
            ],
        });
    });

    it('deduplica le liste e mostra gli inviti ricevuti', () => {
        const wrapper = mount(ProductListIndex);

        expect(wrapper.text()).toContain('Le mie liste');
        expect(wrapper.text()).toContain('Dispensa');
        expect(wrapper.text()).toContain('Inviti ricevuti');
        expect(wrapper.text()).toContain('Casa da Giulia');
        expect(wrapper.findAll('button').filter((button) => button.text() === 'Rinomina')).toHaveLength(1);
    });

    it('crea una lista e mostra la notifica di successo', async () => {
        inertiaRouter.post.mockImplementation((url, payload, options) => {
            options.onSuccess?.();
        });

        const wrapper = mount(ProductListIndex);

        await wrapper.get('input[placeholder="Nuova lista..."]').setValue('Spesa settimanale');
        await wrapper.get('button.btn.btn-primary').trigger('click');

        expect(inertiaRouter.post).toHaveBeenCalledWith(
            '/lists',
            { name: 'Spesa settimanale' },
            expect.objectContaining({ onSuccess: expect.any(Function) }),
        );
        expect(wrapper.text()).toContain('Lista creata!');
        expect(wrapper.get('input[placeholder="Nuova lista..."]').element.value).toBe('');
    });
});