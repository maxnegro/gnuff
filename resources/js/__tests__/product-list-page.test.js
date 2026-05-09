import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import ProductListPage from '@/Pages/Product/List.vue';
import { axiosMock, resetTestState, setPageProps } from '@/__tests__/setup.js';

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('Product/List page', () => {
    beforeEach(() => {
        resetTestState();
        setPageProps({
            active_list: { id: 7, name: 'Attiva' },
        });
        axiosMock.get.mockImplementation((url) => {
            if (url === '/api/products?per_page=100') {
                return Promise.resolve({
                    data: {
                        data: [
                            { id: 1, name: 'Pasta', barcode: '111', image_url: null },
                            { id: 2, name: 'Latte', barcode: '222', image_url: null },
                        ],
                    },
                });
            }

            if (url === '/api/ratings?per_page=100&list_id=7') {
                return Promise.resolve({
                    data: {
                        data: [
                            { product: { id: 1 }, rating: 'ok' },
                        ],
                    },
                });
            }

            return Promise.resolve({ data: { data: [] } });
        });
    });

    it('mostra solo i prodotti con valutazione di default e permette di mostrare tutti', async () => {
        const wrapper = mount(ProductListPage, {
            global: {
                stubs: {
                    ProductRatingModal: true,
                },
            },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('Pasta');
        expect(wrapper.text()).not.toContain('Latte');

        await wrapper.get('button').trigger('click');

        expect(wrapper.text()).toContain('Latte');
    });

    it('filtra per nome dal campo di ricerca', async () => {
        const wrapper = mount(ProductListPage, {
            global: {
                stubs: {
                    ProductRatingModal: true,
                },
            },
        });

        await flushPromises();
        await wrapper.get('input[placeholder="Cerca per nome o barcode..."]').setValue('pas');

        expect(wrapper.text()).toContain('Pasta');
        expect(wrapper.text()).not.toContain('Latte');
    });
});