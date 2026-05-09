import { mount } from '@vue/test-utils';
import TextInput from '@/Components/TextInput.vue';

describe('TextInput', () => {
    it('propaga il valore del model e renderizza un input', async () => {
        const wrapper = mount(TextInput, {
            props: {
                modelValue: 'iniziale',
            },
        });

        const input = wrapper.get('input');

        expect(input.element.value).toBe('iniziale');

        await input.setValue('aggiornato');

        expect(wrapper.emitted()['update:modelValue'][0]).toEqual(['aggiornato']);
    });
});