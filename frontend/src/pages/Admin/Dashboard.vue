/**
 * Дашборд админки (карточки Metronic).
 */
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
</script>

<template>
  <AdminLayout title="Дашборд" breadcrumb="Обзор">
    <Head title="Дашборд" />

    <div class="row g-5 g-xl-8">
      <div class="col-xl-3">
        <Link href="/admin/categories" class="card bg-body hoverable card-xl-stretch mb-xl-8">
          <div class="card-body">
            <div class="text-gray-900 fw-bolder fs-2 mb-2 mt-5">Категории</div>
            <div class="fw-bold text-gray-400">Каталог разделов</div>
          </div>
        </Link>
      </div>
      <div class="col-xl-3">
        <Link href="/admin/products" class="card bg-body hoverable card-xl-stretch mb-xl-8">
          <div class="card-body">
            <div class="text-gray-900 fw-bolder fs-2 mb-2 mt-5">Товары</div>
            <div class="fw-bold text-gray-400">Карточки продукции</div>
          </div>
        </Link>
      </div>
      <div class="col-xl-3">
        <Link href="/admin/leads" class="card bg-body hoverable card-xl-stretch mb-xl-8">
          <div class="card-body">
            <div class="text-gray-900 fw-bolder fs-2 mb-2 mt-5">Заявки</div>
            <div class="fw-bold text-gray-400">Обращения с сайта</div>
          </div>
        </Link>
      </div>
      <div class="col-xl-3">
        <Link href="/admin/settings" class="card bg-body hoverable card-xl-stretch mb-xl-8">
          <div class="card-body">
            <div class="text-gray-900 fw-bolder fs-2 mb-2 mt-5">Настройки</div>
            <div class="fw-bold text-gray-400">Контакты и сайт</div>
          </div>
        </Link>
      </div>
    </div>
  </AdminLayout>
</template>
