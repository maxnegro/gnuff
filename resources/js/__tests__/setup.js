import { config } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { vi } from 'vitest';

const defaultPageProps = {
    auth: {
        user: {
            name: 'Test User',
            email: 'test@example.com',
        },
    },
    user: {
        id: 10,
        name: 'Test User',
        email: 'test@example.com',
    },
    owned: [
        { id: 1, name: 'Lista personale', owner_id: 10, users: [], products: [] },
    ],
    shared: [],
    invitations: [],
    active_list: { id: 1, name: 'Lista personale' },
};

let currentPageProps = structuredClone(defaultPageProps);

export const inertiaRouter = {
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
};

export const axiosMock = {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
};

export function setPageProps(overrides = {}) {
    currentPageProps = {
        ...structuredClone(defaultPageProps),
        ...overrides,
    };

    config.global.mocks.$page = {
        props: currentPageProps,
    };
}

export function resetTestState() {
    setPageProps();
    inertiaRouter.post.mockReset();
    inertiaRouter.put.mockReset();
    inertiaRouter.delete.mockReset();
    axiosMock.get.mockReset();
    axiosMock.post.mockReset();
    axiosMock.put.mockReset();
}

const inertiaLink = defineComponent({
    name: 'InertiaLinkStub',
    props: {
        href: {
            type: String,
            default: '#',
        },
    },
    setup(props, { slots, attrs }) {
        return () => h('a', { ...attrs, href: props.href }, slots.default?.());
    },
});

const inertiaHead = defineComponent({
    name: 'InertiaHeadStub',
    props: {
        title: {
            type: String,
            default: '',
        },
    },
    setup(_, { slots }) {
        return () => slots.default?.() ?? null;
    },
});

vi.mock('@inertiajs/vue3', () => ({
    Link: inertiaLink,
    Head: inertiaHead,
    usePage: () => ({
        props: currentPageProps,
    }),
    router: inertiaRouter,
}));

vi.mock('axios', () => ({
    default: axiosMock,
}));

const routeMock = vi.fn((name) => {
    if (!name) {
        return {
            current: vi.fn(() => false),
        };
    }

    return `/${name}`;
});

routeMock.current = vi.fn(() => false);

global.route = routeMock;

config.global.mocks = {
    route: global.route,
    $page: {
        props: currentPageProps,
    },
};

config.global.stubs = {
    transition: false,
    teleport: true,
};

resetTestState();