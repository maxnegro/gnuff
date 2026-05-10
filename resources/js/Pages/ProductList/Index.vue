<template>
  <div class="app-page-stack space-y-6">
    <div v-if="notification" :class="['rounded-2xl px-4 py-3 text-center text-sm font-medium', notificationType === 'success' ? 'bg-primary-100 text-primary-900 dark:bg-primary-900/40 dark:text-primary-100' : 'bg-red-100 text-red-900 dark:bg-red-900/30 dark:text-red-100']">
      {{ notification }}
    </div>
    <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h1 class="app-page-title">Le mie liste</h1>
          </div>
      <div class="app-surface-soft flex flex-col gap-2 rounded-3xl p-2 sm:flex-row sm:items-center">
      <input v-model="newListName" placeholder="Nuova lista..." class="app-input sm:min-w-[16rem]" />
      <button @click="createList" class="btn btn-primary app-button-primary">Crea</button>
      </div>
    </section>
    <div v-if="lists.length === 0" class="app-panel app-empty-state text-center" :style="{ color: 'var(--app-text-soft)' }">Nessuna lista presente.</div>
    <div v-for="list in lists" :key="list.id" class="app-panel app-panel-pad">
      <div class="flex justify-between items-center mb-2">
        <div>
          <span class="font-bold">{{ list.name }}</span>
          <span v-if="list.owner_id === user.id" class="text-xs text-green-600 ml-2">(owner)</span>
        </div>
        <div class="flex gap-2">
          <button v-if="list.owner_id === user.id" @click="renameList(list)" class="app-button-secondary px-3 py-1.5 text-xs">Rinomina</button>
          <button v-if="list.owner_id === user.id" @click="deleteList(list)" class="app-button-danger px-3 py-1.5 text-xs">Elimina</button>
        </div>
      </div>
      <div class="mb-2 text-xs" :style="{ color: 'var(--app-text-soft)' }">Membri: <span v-for="u in list.users" :key="u.id">{{ u.name }}{{ u.id !== list.users[list.users.length-1].id ? ', ' : '' }}</span></div>
      <div class="flex flex-wrap gap-2 mb-2">
        <span v-for="product in list.products" :key="product.id" class="rounded-full px-3 py-1 text-xs font-medium" :style="{ background: 'color-mix(in srgb, var(--app-bg-muted) 100%, transparent)' }">{{ product.name }}</span>
      </div>
      <div class="mt-3 flex flex-col gap-2 sm:flex-row">
        <input v-model="inviteEmail[list.id]" placeholder="Invita utente (email)" class="app-input" />
        <button @click="inviteUser(list)" class="app-button-primary px-3 py-1.5 text-xs">Invita</button>
      </div>
    </div>
    <div v-if="invitations.length" class="app-panel app-panel-pad mt-6">
      <h2 class="mb-3 text-lg font-semibold">Inviti ricevuti</h2>
      <div v-for="inv in invitations" :key="inv.id" class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <span>{{ inv.list_name }} da {{ inv.owner_name }}</span>
        <div class="flex gap-2">
          <button @click="acceptInvite(inv)" class="app-button-primary px-3 py-1.5 text-xs">Accetta</button>
          <button @click="declineInvite(inv)" class="app-button-danger px-3 py-1.5 text-xs">Rifiuta</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
defineOptions({ layout: AuthenticatedLayout });
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.user || page.props.auth.user);
const lists = computed(() => {
  const all = [ ...(page.props.owned || []), ...(page.props.shared || []) ];
  const seen = new Set();
  return all.filter(l => {
    if (seen.has(l.id)) return false;
    seen.add(l.id);
    return true;
  });
});
const invitations = computed(() => page.props.invitations || []);
const newListName = ref('');
const inviteEmail = ref({});
const notification = ref(null);
const notificationType = ref('success');

function showNotification(msg, type = 'success') {
  notification.value = msg;
  notificationType.value = type;
  setTimeout(() => (notification.value = null), 2500);
}

function createList() {
  if (!newListName.value.trim()) return;
  router.post('/lists', { name: newListName.value }, {
    onSuccess: () => {
      newListName.value = '';
      showNotification('Lista creata!');
    },
    onError: () => showNotification('Errore nella creazione', 'error')
  });
}

function renameList(list) {
  const newName = prompt('Nuovo nome lista:', list.name);
  if (!newName || newName === list.name) return;
  router.put(`/lists/${list.id}`, { name: newName }, {
    onSuccess: () => showNotification('Lista rinominata!'),
    onError: () => showNotification('Errore nella rinomina', 'error')
  });
}

function deleteList(list) {
  if (!confirm('Eliminare la lista?')) return;
  router.delete(`/lists/${list.id}`, {
    onSuccess: () => showNotification('Lista eliminata!'),
    onError: () => showNotification('Errore nella cancellazione', 'error')
  });
}

function inviteUser(list) {
  const email = inviteEmail.value[list.id];
  if (!email) return;
  router.post(`/lists/${list.id}/invite`, { email }, {
    onSuccess: () => {
      inviteEmail.value[list.id] = '';
      showNotification('Invito inviato!');
    },
    onError: () => showNotification('Errore invio invito', 'error')
  });
}

function acceptInvite(inv) {
  router.post(`/lists/${inv.id}/accept`, {}, {
    onSuccess: () => showNotification('Invito accettato!'),
    onError: () => showNotification('Errore accettazione', 'error')
  });
}

function declineInvite(inv) {
  router.post(`/lists/${inv.id}/decline`, {}, {
    onSuccess: () => showNotification('Invito rifiutato!'),
    onError: () => showNotification('Errore rifiuto', 'error')
  });
}
</script>
