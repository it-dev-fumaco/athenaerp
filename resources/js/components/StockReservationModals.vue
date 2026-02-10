<template>
  <div>
    <!-- Add Stock Reservation Modal -->
    <div class="modal fade" id="add-stock-reservation-modal" tabindex="-1">
      <form id="stock-reservation-form" method="POST" action="/create_reservation" autocomplete="off" @submit="handleSubmit">
        <input type="hidden" name="_token" :value="csrfToken">
        <div class="modal-dialog" style="min-width: 40%;">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">New Stock Reservation</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="row m-2">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Item Code</label>
                    <input type="text" class="form-control" name="item_code" v-model="addForm.item_code" readonly>
                  </div>
                  <div class="form-group">
                    <label>Description</label>
                    <textarea rows="4" name="description" class="form-control" style="height: 124px;" v-model="addForm.description" readonly></textarea>
                  </div>
                  <div class="form-group">
                    <label>Notes</label>
                    <textarea rows="4" class="form-control" name="notes" style="height: 124px;" v-model="addForm.notes"></textarea>
                  </div>
                  <div class="form-group for-in-house-type" :class="{ 'd-none': addForm.type !== 'In-house' }">
                    <label for="validity-c">Validity in Day(s)</label>
                    <input type="number" class="form-control" id="validity-c" min="0" v-model.number="addForm.validityDays" @input="updateValidUntilAdd">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Warehouse</label>
                    <select class="form-control" name="warehouse" v-model="addForm.warehouse" @change="onAddWarehouseChange" required>
                      <option value="">Select Warehouse</option>
                      <option v-for="opt in warehousesAdd" :key="opt.id" :value="opt.id">{{ opt.text }}</option>
                    </select>
                  </div>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Reserve Qty</label>
                        <input type="text" name="reserve_qty" class="form-control" v-model="addForm.reserve_qty">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label class="d-block">Available Qty</label>
                        <span class="badge" :class="addForm.availableQty > 0 ? 'badge-success' : 'badge-danger'">
                          <span>{{ addForm.availableQty }}</span>
                          <span>{{ addForm.stock_uom }}</span>
                        </span>
                        <input type="hidden" name="available_qty" :value="addForm.availableQty">
                      </div>
                    </div>
                  </div>
                  <input type="hidden" name="stock_uom" :value="addForm.stock_uom">
                  <div class="form-group">
                    <label>Reservation Type</label>
                    <select name="type" class="form-control" v-model="addForm.type" required>
                      <option value="">Select Type</option>
                      <option value="In-house">In-house</option>
                      <option value="Consignment">Consignment</option>
                      <option value="Website Stocks">Website Stocks</option>
                    </select>
                  </div>
                  <div class="form-group for-in-house-type" :class="{ 'd-none': addForm.type !== 'In-house' }">
                    <label>Sales Person</label>
                    <select class="form-control" name="sales_person" v-model="addForm.sales_person" :required="addForm.type === 'In-house'">
                      <option value="">Select Sales Person</option>
                      <option v-for="opt in salesPersons" :key="opt.id" :value="opt.id">{{ opt.text }}</option>
                    </select>
                  </div>
                  <div class="form-group for-consignment" :class="{ 'd-none': addForm.type !== 'Consignment' }">
                    <label>Branch Warehouse</label>
                    <select class="form-control" name="consignment_warehouse" v-model="addForm.consignment_warehouse" :required="addForm.type === 'Consignment'">
                      <option value="">Select Branch</option>
                      <option v-for="opt in branchWarehouses" :key="opt.id" :value="opt.id">{{ opt.text }}</option>
                    </select>
                  </div>
                  <div class="form-group for-in-house-type" :class="{ 'd-none': addForm.type !== 'In-house' }">
                    <label>Project</label>
                    <select class="form-control" name="project" v-model="addForm.project" :required="addForm.type === 'In-house'">
                      <option value="">Select Project</option>
                      <option v-for="opt in projects" :key="opt.id" :value="opt.id">{{ opt.text }}</option>
                    </select>
                  </div>
                  <div class="form-group for-in-house-type" :class="{ 'd-none': addForm.type !== 'In-house' }">
                    <label>Valid until</label>
                    <input type="date" name="valid_until" class="form-control" v-model="addForm.valid_until">
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
              <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> SAVE</button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Edit Stock Reservation Modal -->
    <div class="modal fade" id="edit-stock-reservation-modal" tabindex="-1">
      <form id="edit-reservation-form" method="POST" action="/update_reservation" autocomplete="off" @submit="handleSubmit">
        <input type="hidden" name="_token" :value="csrfToken">
        <input type="hidden" name="id" v-model="editForm.id">
        <div class="modal-dialog" style="min-width: 40%;">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Edit Stock Reservation</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="row m-2">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Item Code</label>
                    <input type="text" class="form-control" name="item_code" v-model="editForm.item_code" readonly>
                  </div>
                  <div class="form-group">
                    <label>Description</label>
                    <textarea rows="4" name="description" class="form-control" style="height: 124px;" v-model="editForm.description" readonly></textarea>
                  </div>
                  <div class="form-group">
                    <label>Notes</label>
                    <textarea rows="4" class="form-control" name="notes" id="notes-e" style="height: 124px;" v-model="editForm.notes"></textarea>
                  </div>
                  <div class="form-group for-in-house-type" :class="{ 'd-none': editForm.type !== 'In-house' }">
                    <label for="validity-e">Validity in Day(s)</label>
                    <input type="number" class="form-control" id="validity-e" min="0" :value="editForm.validityDays" readonly>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Warehouse</label>
                    <input type="text" class="form-control" :value="editForm.warehouse" readonly>
                    <input type="hidden" name="warehouse" v-model="editForm.warehouse">
                  </div>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Reserve Qty</label>
                        <input type="text" name="reserve_qty" class="form-control" v-model="editForm.reserve_qty">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label class="d-block">Available Qty</label>
                        <span class="badge" :class="editForm.availableQty > 0 ? 'badge-success' : 'badge-danger'">
                          <span>{{ editForm.availableQty }}</span>
                          <span>{{ editForm.stock_uom }}</span>
                        </span>
                        <input type="hidden" name="available_qty" v-model="editForm.availableQty">
                      </div>
                    </div>
                  </div>
                  <input type="hidden" name="stock_uom" :value="editForm.stock_uom">
                  <div class="form-group">
                    <label>Reservation Type</label>
                    <input type="text" class="form-control" :value="editForm.type" readonly>
                    <input type="hidden" name="type" :value="editForm.type">
                  </div>
                  <div class="form-group for-in-house-type" :class="{ 'd-none': editForm.type !== 'In-house' }">
                    <label>Sales Person</label>
                    <select class="form-control" name="sales_person" v-model="editForm.sales_person">
                      <option v-for="opt in editSalesPersons" :key="opt.id" :value="opt.id">{{ opt.text }}</option>
                    </select>
                  </div>
                  <div class="form-group for-consignment" :class="{ 'd-none': editForm.type !== 'Consignment' }">
                    <label>Branch Warehouse</label>
                    <select class="form-control" name="consignment_warehouse" v-model="editForm.consignment_warehouse">
                      <option v-for="opt in editBranchWarehouses" :key="opt.id" :value="opt.id">{{ opt.text }}</option>
                    </select>
                  </div>
                  <div class="form-group for-in-house-type" :class="{ 'd-none': editForm.type !== 'In-house' }">
                    <label>Project</label>
                    <select class="form-control" name="project" v-model="editForm.project">
                      <option v-for="opt in editProjects" :key="opt.id" :value="opt.id">{{ opt.text }}</option>
                    </select>
                  </div>
                  <div class="form-group for-in-house-type" :class="{ 'd-none': editForm.type !== 'In-house' }">
                    <label>Valid until</label>
                    <input type="date" name="valid_until" class="form-control" v-model="editForm.valid_until">
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
              <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> UPDATE</button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Cancel Stock Reservation Modal -->
    <div class="modal fade" id="cancel-stock-reservation-modal" tabindex="-1">
      <form id="cancel-reservation-form" method="POST" action="/cancel_reservation" autocomplete="off" @submit="handleSubmit">
        <input type="hidden" name="_token" :value="csrfToken">
        <input type="hidden" name="stock_reservation_id" v-model="cancelReservationId">
        <div class="modal-dialog" style="min-width: 40%;">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Cancel Stock Reservation</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to cancel Stock Reservation No. <strong class="reservation-id">{{ cancelReservationId }}</strong>?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
              <button type="submit" class="btn btn-danger btn-lg"><i class="fa fa-check"></i> CONFIRM CANCEL</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

const REFRESH_EVENT = 'item-profile-stock-reservation-refresh';

function showModal(modalId) {
  const modalEl = document.getElementById(modalId);
  if (!modalEl) return;
  if (typeof window.$ !== 'undefined') {
    window.$(`#${modalId}`).modal('show');
  } else if (window.bootstrap?.Modal) {
    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }
}

function hideModal(modalId) {
  const modalEl = document.getElementById(modalId);
  if (!modalEl) return;
  if (typeof window.$ !== 'undefined') {
    window.$(`#${modalId}`).modal('hide');
  } else if (window.bootstrap?.Modal) {
    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.hide();
  }
}

export default {
  name: 'StockReservationModals',
  data() {
    return {
      csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      addForm: {
        item_code: '',
        description: '',
        notes: '',
        warehouse: '',
        reserve_qty: '0',
        type: '',
        sales_person: '',
        consignment_warehouse: '',
        project: '',
        valid_until: '',
        validityDays: 0,
        stock_uom: '',
        availableQty: 0,
      },
      editForm: {
        id: '',
        item_code: '',
        description: '',
        notes: '',
        warehouse: '',
        reserve_qty: '0',
        type: '',
        sales_person: '',
        consignment_warehouse: '',
        project: '',
        valid_until: '',
        validityDays: 0,
        stock_uom: '',
        availableQty: 0,
      },
      cancelReservationId: '',
      warehousesAdd: [],
      salesPersons: [],
      projects: [],
      branchWarehouses: [],
      editSalesPersons: [],
      editBranchWarehouses: [],
      editProjects: [],
    };
  },
  mounted() {
    document.addEventListener('click', this.handleDocumentClick);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleDocumentClick);
  },
  methods: {
    handleDocumentClick(event) {
      const addBtn = event.target.closest('#add-stock-reservation-btn');
      if (addBtn) {
        event.preventDefault();
        this.openAddModal();
        return;
      }
      const editBtn = event.target.closest('.edit-stock-reservation-btn');
      if (editBtn) {
        event.preventDefault();
        const reservationId = editBtn.dataset?.reservationId;
        if (reservationId) this.openEditModal(reservationId);
        return;
      }
      const cancelBtn = event.target.closest('.cancel-stock-reservation-btn');
      if (cancelBtn) {
        event.preventDefault();
        const reservationId = cancelBtn.dataset?.reservationId;
        if (reservationId) this.openCancelModal(reservationId);
      }
    },

    async openAddModal() {
      const selectedCodeEl = document.getElementById('selected-item-code');
      const itemCode = selectedCodeEl?.textContent?.trim();
      if (!itemCode) return;

      this.addForm = {
        item_code: '',
        description: '',
        notes: '',
        warehouse: '',
        reserve_qty: '0',
        type: '',
        sales_person: '',
        consignment_warehouse: '',
        project: '',
        valid_until: '',
        validityDays: 0,
        stock_uom: '',
        availableQty: 0,
      };
      this.warehousesAdd = [];
      this.salesPersons = [];
      this.projects = [];
      this.branchWarehouses = [];

      try {
        const [itemRes, whRes, salesRes, projRes, branchRes] = await Promise.all([
          axios.get(`/get_item_details/${itemCode}`, { params: { json: 'true' } }),
          axios.get('/warehouses_with_stocks', { params: { item_code: itemCode, q: '' } }),
          axios.get('/sales_persons', { params: { q: '' } }),
          axios.get('/projects', { params: { q: '' } }),
          axios.get('/consignment_warehouses', { params: { q: '' } }),
        ]);
        const data = itemRes.data;
        this.addForm.item_code = data.name;
        this.addForm.description = data.description || '';
        this.addForm.stock_uom = data.stock_uom || '';
        const whList = Array.isArray(whRes.data) ? whRes.data : [];
        this.warehousesAdd = whList.map((o) => ({ id: o.id ?? o.name, text: o.text ?? o.name ?? o.id }));
        this.salesPersons = (Array.isArray(salesRes.data) ? salesRes.data : []).map((o) => ({ id: o.id ?? o.name, text: o.text ?? o.name ?? o.id }));
        this.projects = (Array.isArray(projRes.data) ? projRes.data : []).map((o) => ({ id: o.id ?? o.name, text: o.text ?? o.name ?? o.id }));
        this.branchWarehouses = (Array.isArray(branchRes.data) ? branchRes.data : []).map((o) => ({ id: o.id ?? o.name, text: o.text ?? o.name ?? o.id }));
        const d = new Date();
        d.setDate(d.getDate() + 30);
        this.addForm.valid_until = d.toISOString().slice(0, 10);
        showModal('add-stock-reservation-modal');
      } catch (_) {
        if (typeof window.showNotification === 'function') {
          window.showNotification('danger', 'Failed to load item details.', 'fa fa-info');
        }
      }
    },

    updateValidUntilAdd() {
      const d = new Date();
      d.setDate(d.getDate() + (this.addForm.validityDays || 0));
      this.addForm.valid_until = d.toISOString().slice(0, 10);
    },

    onAddWarehouseChange() {
      if (!this.addForm.warehouse || !this.addForm.item_code) return;
      axios.get(`/get_available_qty/${this.addForm.item_code}/${this.addForm.warehouse}`)
        .then((res) => {
          this.addForm.availableQty = parseInt(res.data, 10) || 0;
        })
        .catch(() => {
          this.addForm.availableQty = 0;
        });
    },

    async openEditModal(reservationId) {
      try {
        const res = await axios.get(`/get_stock_reservation_details/${reservationId}`);
        const data = res.data;
        this.editForm = {
          id: data.name,
          item_code: data.item_code || '',
          description: data.description || '',
          notes: data.notes || '',
          warehouse: data.warehouse || '',
          reserve_qty: data.reserve_qty ?? '0',
          type: data.type || '',
          sales_person: data.sales_person || '',
          consignment_warehouse: data.consignment_warehouse || '',
          project: data.project || '',
          valid_until: data.valid_until ? data.valid_until.slice(0, 10) : '',
          validityDays: data.valid_until ? this.validityDays(data.valid_until) : 0,
          stock_uom: data.stock_uom || '',
          availableQty: 0,
        };
        this.editSalesPersons = data.sales_person ? [{ id: data.sales_person, text: data.sales_person }] : [];
        this.editBranchWarehouses = data.consignment_warehouse ? [{ id: data.consignment_warehouse, text: data.consignment_warehouse }] : [];
        this.editProjects = data.project ? [{ id: data.project, text: data.project }] : [];

        const qtyRes = await axios.get(`/get_available_qty/${data.item_code}/${data.warehouse}`);
        this.editForm.availableQty = parseInt(qtyRes.data, 10) || 0;
        showModal('edit-stock-reservation-modal');
      } catch (_) {
        if (typeof window.showNotification === 'function') {
          window.showNotification('danger', 'Failed to load reservation details.', 'fa fa-info');
        }
      }
    },

    validityDays(validUntil) {
      const now = new Date();
      now.setHours(0, 0, 0, 0);
      const end = new Date(validUntil);
      end.setHours(0, 0, 0, 0);
      const diff = end.getTime() - now.getTime();
      return diff > 0 ? Math.floor(diff / (1000 * 60 * 60 * 24)) : 0;
    },

    openCancelModal(reservationId) {
      this.cancelReservationId = reservationId;
      showModal('cancel-stock-reservation-modal');
    },

    handleSubmit(event) {
      event.preventDefault();
      const form = event.target;
      const formData = new FormData(form);
      const action = form.getAttribute('action');
      if (!action) return;

      axios
        .post(action, formData)
        .then((response) => {
          const data = response.data;
          const isError = data.error === 1 || data.error === true;
          const message = data.modal_message ?? data.message ?? (isError ? 'An error occurred.' : 'Saved.');
          if (typeof window.showNotification === 'function') {
            window.showNotification(isError ? 'danger' : 'success', message, isError ? 'fa fa-info' : 'fa fa-check');
          }
          if (!isError) {
            if (form.id === 'stock-reservation-form') hideModal('add-stock-reservation-modal');
            if (form.id === 'edit-reservation-form') hideModal('edit-stock-reservation-modal');
            if (form.id === 'cancel-reservation-form') hideModal('cancel-stock-reservation-modal');
            document.dispatchEvent(new CustomEvent(REFRESH_EVENT));
          }
        })
        .catch((err) => {
          const message =
            err.response?.data?.modal_message ??
            err.response?.data?.message ??
            (err.response?.status === 422 ? 'Validation failed.' : 'Something went wrong.');
          if (typeof window.showNotification === 'function') {
            window.showNotification('danger', message, 'fa fa-info');
          }
        });
    },
  },
};
</script>
