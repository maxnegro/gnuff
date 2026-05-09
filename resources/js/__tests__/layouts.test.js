import { mount } from '@vue/test-utils';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

describe('layout principali', () => {
    it('renderizza la navigazione autenticata e la lista attiva', () => {
        const wrapper = mount(AuthenticatedLayout, {
            slots: {
                default: '<div>Contenuto pagina</div>',
                header: '<h1>Header pagina</h1>',
            },
            global: {
                mocks: {
                    $page: {
                        props: {
                            auth: {
                                user: {
                                    name: 'Test User',
                                    email: 'test@example.com',
                                },
                            },
                        },
                    },
                },
            },
        });

        expect(wrapper.text()).toContain('Dashboard');
        expect(wrapper.text()).toContain('Lista attiva:');
        expect(wrapper.text()).toContain('Header pagina');
        expect(wrapper.text()).toContain('Contenuto pagina');
    });

    it('renderizza il contenitore guest con lo slot centrale', () => {
        const wrapper = mount(GuestLayout, {
            slots: {
                default: '<form>Login form</form>',
            },
        });

        expect(wrapper.find('main').exists()).toBe(false);
        expect(wrapper.text()).toContain('Login form');
        expect(wrapper.find('img[alt="Logo"]').exists()).toBe(true);
    });
});