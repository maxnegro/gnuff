<template>
  <div class="p-4 max-w-2xl mx-auto">
    <div v-if="notification" :class="['mb-4 p-2 rounded text-center', notificationType === 'success' ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900']">
      {{ notification }}
    </div>
    <h1 class="text-2xl font-bold mb-4">Le mie liste</h1>
    <div class="mb-4 flex gap-2">
      <input v-model="newListName" placeholder="Nuova lista..." class="input input-bordered" />
      <button @click="createList" class="btn btn-primary">Crea</button>
    </div>
    <div v-if="lists.length === 0" class="text-center text-gray-400 py-8">Nessuna lista presente.</div>
    <div v-for="list in lists" :key="list.id" class="mb-4 border rounded p-3 bg-white">
      <div class="flex justify-between items-center mb-2">
        <div>
          <span class="font-bold">{{ list.name }}</span>
          <span v-if="list.owner_id === user.id" class="text-xs text-green-600 ml-2">(owner)</span>
        </div>
        <div class="flex gap-2">
          <button v-if="list.owner_id === user.id" @click="renameList(list)" class="btn btn-xs">Rinomina</button>
          <button v-if="list.owner_id === user.id" @click="deleteList(list)" class="btn btn-xs btn-error">Elimina</button>
        </div>
      </div>
      <div class="text-xs text-gray-500 mb-2">Membri: <span v-for="u in list.users" :key="u.id">{{ u.name }}{{ u.id !== list.users[list.users.length-1].id ? ', ' : '' }}</span></div>
      <div class="flex flex-wrap gap-2 mb-2">
        <span v-for="product in list.products" :key="product.id" class="px-2 py-1 bg-gray-100 rounded">{{ product.name }}</span>
      </div>
      <div class="flex gap-2 mt-2">
        <input v-model="inviteEmail[list.id]" placeholder="Invita utente (email)" class="input input-xs" />
        <button @click="inviteUser(list)" class="btn btn-xs">Invita</button>
      </div>
    </div>
    <div v-if="invitations.length" class="mt-6">
      <h2 class="font-bold mb-2">Inviti ricevuti</h2>
      <div v-for="inv in invitations" :key="inv.id" class="flex gap-2 items-center mb-2">
        <span>{{ inv.list_name }} da {{ inv.owner_name }}</span>
        <button @click="acceptInvite(inv)" class="btn btn-xs btn-success">Accetta</button>
        <button @click="declineInvite(inv)" class="btn btn-xs btn-error">Rifiuta</button>
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

<style scoped>
.input { border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.5rem; }
.btn { background: #10b981; color: #fff; border-radius: 0.375rem; padding: 0.25rem 0.75rem; font-size: 0.9rem; }
.btn-xs { font-size: 0.75rem; padding: 0.15rem 0.5rem; }
.btn-error { background: #ef4444; }
.btn-success { background: #22c55e; }
.btn-primary { background: #2563eb; }
</style>