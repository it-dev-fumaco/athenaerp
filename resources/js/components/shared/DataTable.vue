<template>
  <div class="table-responsive">
    <div v-if="loading" class="text-center p-3">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <table v-else class="table table-bordered table-hover m-0">
      <thead>
        <tr>
          <th
            v-for="col in columns"
            :key="col.key"
            class="align-middle"
            :class="col.thClass"
            :style="col.thStyle"
          >
            <span v-if="col.sortKey" class="d-flex align-items-center">
              {{ col.label }}
              <button
                type="button"
                class="btn btn-link btn-sm p-0 ml-1"
                :class="{ 'font-weight-bold': sortKey === col.sortKey }"
                @click="$emit('sort', col.sortKey)"
              >
                {{ sortKey === col.sortKey && sortDir === 'asc' ? '↑' : '↓' }}
              </button>
            </span>
            <span v-else>{{ col.label }}</span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in data" :key="rowKey ? row[rowKey] : index">
          <td
            v-for="col in columns"
            :key="col.key"
            class="align-middle"
            :class="col.tdClass"
          >
            <slot name="cell" :column="col" :row="row" :value="row[col.key]">
              {{ row[col.key] }}
            </slot>
          </td>
        </tr>
        <tr v-if="!data || data.length === 0">
          <td :colspan="columns.length" class="text-center text-muted">No records.</td>
        </tr>
      </tbody>
    </table>
    <div
      v-if="pagination && pagination.lastPage > 1"
      class="card-footer clearfix mt-2"
    >
      <ul class="pagination pagination-sm m-0 float-right">
        <li class="page-item" :class="{ disabled: pagination.currentPage <= 1 }">
          <a
            class="page-link"
            href="#"
            @click.prevent="pagination.currentPage > 1 && $emit('page-change', pagination.currentPage - 1)"
          >«</a>
        </li>
        <li
          v-for="p in visiblePages"
          :key="p"
          class="page-item"
          :class="{ active: p === pagination.currentPage }"
        >
          <a class="page-link" href="#" @click.prevent="$emit('page-change', p)">{{ p }}</a>
        </li>
        <li class="page-item" :class="{ disabled: pagination.currentPage >= pagination.lastPage }">
          <a
            class="page-link"
            href="#"
            @click.prevent="pagination.currentPage < pagination.lastPage && $emit('page-change', pagination.currentPage + 1)"
          >»</a>
        </li>
      </ul>
      <small class="text-muted">Total: {{ pagination.total ?? 0 }}</small>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DataTable',
  props: {
    columns: { type: Array, required: true },
    data: { type: Array, default: () => [] },
    rowKey: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    pagination: {
      type: Object,
      default: null,
    },
    sortKey: { type: String, default: '' },
    sortDir: { type: String, default: 'asc' },
  },
  emits: ['page-change', 'sort'],
  computed: {
    visiblePages() {
      if (!this.pagination || this.pagination.lastPage <= 1) return [];
      const current = this.pagination.currentPage;
      const last = this.pagination.lastPage;
      const delta = 2;
      const range = [];
      for (let p = Math.max(1, current - delta); p <= Math.min(last, current + delta); p++) {
        range.push(p);
      }
      return range;
    },
  },
};
</script>
