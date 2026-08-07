<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import LocationCapture, {
  type LocationData,
  type LocationCaptureConfig,
} from "./components/LocationCapture.vue";
import PublicLayout from "@/layouts/PublicLayout.vue";
import type { FormFlowUiVariant } from "@/pages/form-flow/core/components/formFlowUiVariant";

interface Props {
  flow_id: string;
  step: string;
  config?: LocationCaptureConfig;
  ui_variant?: FormFlowUiVariant | string | null;
  preview_mode?: boolean;
}

const props = defineProps<Props>();

function handleSubmit(locationData: LocationData) {
  if (props.preview_mode) return;
  // Submit to FormFlowController
  router.post(`/form-flow/${props.flow_id}/step/${props.step}`, {
    data: locationData as unknown as Record<string, any>,
  });
}

function handleCancel() {
  if (props.preview_mode) return;
  // Cancel the flow
  router.post(`/form-flow/${props.flow_id}/cancel`);
}
</script>

<template>
  <PublicLayout>
    <LocationCapture
      :flow-id="flow_id"
      :config="config"
      :ui-variant="ui_variant"
      :preview-mode="preview_mode"
      @submit="handleSubmit"
      @cancel="handleCancel"
    />
  </PublicLayout>
</template>
