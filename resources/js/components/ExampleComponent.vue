<template>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">Example Component</div>
          <div class="card-body">
            <p class="mb-2">I'm an example component.</p>
            <button type="button" class="btn btn-primary btn-sm" @click="showModal = true">
              Open shared Modal
            </button>
          </div>
        </div>
        <div class="card mt-3">
          <div class="card-header">Shared DataTable example</div>
          <div class="card-body p-0">
            <shared-data-table
              :columns="tableColumns"
              :data="tableData"
              :loading="false"
            />
          </div>
        </div>
      </div>
    </div>
    <shared-modal v-model="showModal" title="Shared Modal">
      <p>This modal uses the shared <code>Modal.vue</code> component.</p>
      <template #footer>
        <button type="button" class="btn btn-secondary" @click="showModal = false">Close</button>
      </template>
    </shared-modal>
  </div>
</template>

<script>
import SharedModal from './shared/Modal.vue';
import SharedDataTable from './shared/DataTable.vue';

export default {
  name: 'ExampleComponent',
  components: { SharedModal, SharedDataTable },
  data() {
    return {
      showModal: false,
      tableColumns: [
        { key: 'name', label: 'Name' },
        { key: 'value', label: 'Value' },
      ],
      tableData: [
        { name: 'Item A', value: '10' },
        { name: 'Item B', value: '20' },
      ],
    };
  },
  mounted() {
    console.log('ExampleComponent mounted.');
  },
};
</script>
