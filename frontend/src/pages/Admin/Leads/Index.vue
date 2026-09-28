/**
 * Заявки — таблица Metronic со сменой статуса.
 */
<script setup>
import { onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import leadsApi from '@/api/modules/admin/leads';

/** Список */
const items = ref([]);
/** Загрузка */
const isLoading = ref(false);
/** Ошибка */
const errorText = ref('');
/** ID заявок, для которых выполняется retry */
const retryingLeadIds = ref(new Set());

/** Статусы */
const statuses = [
    { value: 'new', label: 'Новая' },
    { value: 'in_progress', label: 'В работе' },
    { value: 'done', label: 'Закрыта' },
    { value: 'rejected', label: 'Отклонена' },
];

/**
 * Загрузить заявки.
 *
 * @returns {Promise<void>}
 */
const load = async () => {
    isLoading.value = true;
    errorText.value = '';
    try {
        const response = await leadsApi.index();
        const data = response.data?.data ?? response.data;
        items.value = data?.data ?? data ?? [];
    } catch (error) {
        errorText.value = error.response?.data?.message || 'Не удалось загрузить';
    } finally {
        isLoading.value = false;
    }
};

/**
 * Нормализовать статус к строке.
 *
 * @param {unknown} status Статус
 * @returns {string}
 */
const statusValue = (status) => {
    if (typeof status === 'string') return status;
    if (status && typeof status === 'object' && 'value' in status) {
        return /** @type {{value: string}} */ (status).value;
    }
    return String(status || '');
};

/**
 * Сменить статус.
 *
 * @param {object} lead Заявка
 * @param {Event} event Change
 * @returns {Promise<void>}
 */
const changeStatus = async (lead, event) => {
    const status = /** @type {HTMLSelectElement} */ (event.target).value;
    try {
        await leadsApi.update(lead.id, { status });
        lead.status = status;
    } catch (error) {
        errorText.value = error.response?.data?.message || 'Не удалось обновить';
        await load();
    }
};

/**
 * URL к файлу, лежащему в `storage` (public disk).
 *
 * @param {string} path Путь внутри диска `public` (например: `leads/attachments/a.pdf`)
 * @returns {string}
 */
const fileUrl = (path) => `/storage/${path}`;

/**
 * Читаемая метка состояния доставки уведомления.
 *
 * @param {object} lead Заявка
 * @returns {string}
 */
const emailDeliveryLabel = (lead) => ({
    pending: 'Ожидает постановки',
    queued: 'В очереди',
    sending: 'Отправляется',
    sent: 'Отправлено',
    failed: 'Ошибка доставки',
    dispatch_failed: 'Не поставлено в очередь',
}[lead.email_delivery_status] || (lead.email_sent_at ? 'Отправлено' : 'Нет данных'));

/**
 * CSS-класс статуса доставки.
 *
 * @param {object} lead Заявка
 * @returns {string}
 */
const emailDeliveryClass = (lead) => ({
    pending: 'badge-light-warning',
    queued: 'badge-light-warning',
    sending: 'badge-light-primary',
    sent: 'badge-light-success',
    failed: 'badge-light-danger',
    dispatch_failed: 'badge-light-danger',
}[lead.email_delivery_status] || 'badge-light-secondary');

/**
 * Можно ли вручную повторить уведомление.
 *
 * @param {object} lead Заявка
 * @returns {boolean}
 */
const canRetryEmail = (lead) => ['pending', 'failed', 'dispatch_failed'].includes(lead.email_delivery_status);

/**
 * Проверяет, выполняется ли уже повторная постановка конкретной заявки.
 *
 * @param {object} lead Заявка
 * @returns {boolean}
 */
const isRetryingEmail = (lead) => retryingLeadIds.value.has(lead.id);

/**
 * Синхронизирует карточку заявки в таблице и открытом диалоге.
 *
 * @param {object} updated Обновлённая заявка из API
 * @returns {void}
 */
const applyLeadUpdate = (updated) => {
    const row = items.value.find((lead) => lead.id === updated.id);
    if (row) Object.assign(row, updated);
    if (leadView.value?.id === updated.id) Object.assign(leadView.value, updated);
};

/**
 * Вручную повторяет доставку уведомления после ошибки.
 *
 * @param {object} lead Заявка
 * @returns {Promise<void>}
 */
const retryEmail = async (lead) => {
    if (isRetryingEmail(lead)) return;

    errorText.value = '';
    retryingLeadIds.value = new Set([...retryingLeadIds.value, lead.id]);
    try {
        const response = await leadsApi.retryEmail(lead.id);
        const updated = response.data?.data ?? response.data;
        applyLeadUpdate(updated);
    } catch (error) {
        errorText.value = error.response?.data?.message || 'Не удалось повторить отправку';
    } finally {
        const nextIds = new Set(retryingLeadIds.value);
        nextIds.delete(lead.id);
        retryingLeadIds.value = nextIds;
    }
};

/**
 * Форматирует дату/время заявки для таблицы и модалки.
 *
 * @param {string|null|undefined} value ISO-дата (created_at / email_sent_at)
 * @returns {string}
 */
const formatDateTime = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

/**
 * Открытый диалог просмотра заявки.
 *
 * @type {import('vue').Ref<boolean>}
 */
const isLeadViewOpen = ref(false);

/**
 * Текущая заявка для просмотра.
 *
 * @type {import('vue').Ref<object|null>}
 */
const leadView = ref(null);

/**
 * Открыть диалог с подробной информацией заявки.
 *
 * @param {object} lead Заявка
 * @returns {void}
 */
const openLeadView = (lead) => {
    leadView.value = lead;
    isLeadViewOpen.value = true;
};

/**
 * Закрыть модалку просмотра заявки.
 *
 * @returns {void}
 */
const closeLeadView = () => {
    isLeadViewOpen.value = false;
    leadView.value = null;
};

onMounted(load);
</script>

<template>
  <AdminLayout title="Заявки" breadcrumb="Заявки">
    <Head title="Заявки" />

    <div v-if="errorText" class="alert alert-danger mb-5">{{ errorText }}</div>

    <div class="card">
      <div class="card-header border-0 pt-6">
        <div class="card-title">
          <h3 class="fw-bolder m-0">Обращения с сайта</h3>
        </div>
      </div>
      <div class="card-body pt-0">
        <div v-if="isLoading" class="text-muted py-10">Загрузка…</div>
        <div v-else-if="!items.length" class="text-muted py-10">Заявок пока нет.</div>
        <div v-else class="table-responsive">
          <el-table :data="items" style="width: 100%">
            <el-table-column prop="id" label="ID" width="70" />

            <el-table-column label="Дата" width="140">
              <template #default="{ row }">
                {{ formatDateTime(row.created_at) }}
              </template>
            </el-table-column>

            <el-table-column prop="name" label="Имя" />
            <el-table-column prop="phone" label="Телефон" />
            <el-table-column prop="email" label="Email" />

            <el-table-column label="Сообщение">
              <template #default="{ row }">
                <div style="white-space: normal; word-break: break-word;">{{ row.message }}</div>
              </template>
            </el-table-column>

            <el-table-column label="Статус">
              <template #default="{ row }">
                <select
                  class="form-select form-select-solid form-select-sm"
                  :value="statusValue(row.status)"
                  @change="changeStatus(row, $event)"
                >
                  <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
              </template>
            </el-table-column>

            <el-table-column label="Уведомление" width="190">
              <template #default="{ row }">
                <div class="d-flex flex-column gap-2 align-items-start">
                  <span class="badge" :class="emailDeliveryClass(row)">
                    {{ emailDeliveryLabel(row) }}
                  </span>
                  <button
                    v-if="canRetryEmail(row)"
                    type="button"
                    class="btn btn-sm btn-light-primary"
                    :disabled="isRetryingEmail(row)"
                    @click="retryEmail(row)"
                  >
                    {{ isRetryingEmail(row) ? 'Постановка…' : 'Повторить' }}
                  </button>
                </div>
              </template>
            </el-table-column>

            <el-table-column label="Просмотр" align="right">
              <template #default="{ row }">
                <button
                  type="button"
                  class="btn btn-sm btn-light btn-active-light-primary"
                  @click="openLeadView(row)"
                >
                  Просмотр заявки
                </button>
              </template>
            </el-table-column>
          </el-table>

        </div>
      </div>
    </div>

    <!-- Модалка в стиле Metronic -->
    <Teleport to="body">
      <div
        v-if="isLeadViewOpen && leadView"
        class="modal fade show d-block"
        tabindex="-1"
        aria-modal="true"
        role="dialog"
      >
        <div class="modal-dialog modal-dialog-centered admin-modal">
          <div class="modal-content">
            <div class="modal-header">
              <h2 class="fw-bolder">Просмотр заявки #{{ leadView.id }}</h2>
              <div
                class="btn btn-icon btn-sm btn-active-icon-primary"
                @click="closeLeadView"
              >
                <span class="svg-icon svg-icon-1">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <path
                      fill="currentColor"
                      d="M6.7 5.3a1 1 0 0 0-1.4 1.4L10.6 12l-5.3 5.3a1 1 0 1 0 1.4 1.4L12 13.4l5.3 5.3a1 1 0 0 0 1.4-1.4L13.4 12l5.3-5.3a1 1 0 0 0-1.4-1.4L12 10.6 6.7 5.3Z"
                    />
                  </svg>
                </span>
              </div>
            </div>

            <div class="modal-body">
              <div class="admin-modal__scroll">
                <div class="fv-row mb-7">
                  <label class="fs-6 fw-bold mb-2">Дата создания</label>
                  <div class="fw-bolder">{{ formatDateTime(leadView.created_at) }}</div>
                </div>

                <div class="row g-9 mb-7">
                  <div class="col-md-6 fv-row">
                    <label class="fs-6 fw-bold mb-2">Имя</label>
                    <div class="fw-bolder">{{ leadView.name }}</div>
                  </div>
                  <div class="col-md-6 fv-row">
                    <label class="fs-6 fw-bold mb-2">Телефон</label>
                    <div class="fw-bolder">{{ leadView.phone }}</div>
                  </div>
                </div>

                <div class="fv-row mb-7">
                  <label class="fs-6 fw-bold mb-2">Email</label>
                  <div class="fw-bolder">{{ leadView.email }}</div>
                </div>

                <div class="fv-row mb-7">
                  <label class="fs-6 fw-bold mb-2">Сообщение</label>
                  <div class="p-4 bg-light rounded" style="white-space: pre-wrap;">{{ leadView.message }}</div>
                </div>

                <div class="row g-9 mb-7">
                  <div class="col-md-6 fv-row">
                    <label class="fs-6 fw-bold mb-2">Статус заявки</label>
                    <select
                      class="form-select form-select-solid"
                      :value="statusValue(leadView.status)"
                      @change="changeStatus(leadView, $event)"
                    >
                      <option v-for="s in statuses" :key="s.value" :value="s.value">
                        {{ s.label }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-6 fv-row">
                    <label class="fs-6 fw-bold mb-2">Уведомление менеджеру</label>
                    <div class="d-flex flex-column gap-2 align-items-start">
                      <span class="badge" :class="emailDeliveryClass(leadView)">
                        {{ emailDeliveryLabel(leadView) }}
                      </span>
                      <button
                        v-if="canRetryEmail(leadView)"
                        type="button"
                        class="btn btn-sm btn-light-primary"
                        :disabled="isRetryingEmail(leadView)"
                        @click="retryEmail(leadView)"
                      >
                        {{ isRetryingEmail(leadView) ? 'Постановка…' : 'Повторить отправку' }}
                      </button>
                    </div>
                  </div>
                </div>

                <div class="fv-row mb-7">
                  <label class="fs-6 fw-bold mb-2">Детали доставки уведомления</label>
                  <div class="row g-5">
                    <div class="col-md-6">
                      <div class="text-muted fs-7">Получатель</div>
                      <div class="fw-bolder">{{ leadView.email_recipient || '—' }}</div>
                    </div>
                    <div class="col-md-3">
                      <div class="text-muted fs-7">Попыток</div>
                      <div class="fw-bolder">{{ leadView.email_attempts ?? 0 }}</div>
                    </div>
                    <div class="col-md-3">
                      <div class="text-muted fs-7">Отправлено</div>
                      <div class="fw-bolder">{{ formatDateTime(leadView.email_sent_at) }}</div>
                    </div>
                  </div>
                  <div v-if="leadView.email_error" class="alert alert-danger mt-4 mb-0 py-3">
                    {{ leadView.email_error }}
                  </div>
                </div>

                <div class="fv-row mb-7">
                  <label class="fs-6 fw-bold mb-2">Материалы к заявке</label>
                  <template v-if="leadView.attachments?.attachment?.length">
                    <div
                      v-for="(f, idx) in leadView.attachments.attachment"
                      :key="f?.path || idx"
                      class="mb-2"
                    >
                      <a :href="fileUrl(f.path)" :download="f.original_name" target="_blank">
                        {{ f.original_name }}
                      </a>
                    </div>
                  </template>
                  <div v-else class="text-muted">-</div>
                </div>

                <div class="fv-row mb-2">
                  <label class="fs-6 fw-bold mb-2">Карточка предприятия</label>
                  <template v-if="leadView.attachments?.company_card?.length">
                    <div
                      v-for="(f, idx) in leadView.attachments.company_card"
                      :key="f?.path || idx"
                      class="mb-2"
                    >
                      <a :href="fileUrl(f.path)" :download="f.original_name" target="_blank">
                        {{ f.original_name }}
                      </a>
                    </div>
                  </template>
                  <div v-else class="text-muted">-</div>
                </div>
              </div>
            </div>

            <div class="modal-footer flex-center">
              <button type="button" class="btn btn-light" @click="closeLeadView">
                Закрыть
              </button>
            </div>
          </div>
        </div>
      </div>
      <div v-if="isLeadViewOpen" class="modal-backdrop fade show" />
    </Teleport>
  </AdminLayout>
</template>
