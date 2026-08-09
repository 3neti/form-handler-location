<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import LocationCapture, {
  type LocationData,
  type LocationCaptureConfig,
} from "./components/LocationCapture.vue";
import PublicLayout from "@/layouts/PublicLayout.vue";
import type { FormFlowUiVariant } from "@/pages/form-flow/core/components/formFlowUiVariant";

interface PackageVersion {
  name: string;
  version: string;
}

interface Props {
  flow_id: string;
  step: string;
  config?: LocationCaptureConfig;
  ui_variant?: FormFlowUiVariant | string | null;
  action_placement?: "inline" | "bottom" | "bottom_sticky" | "viewport_bottom" | string | null;
  ui_layout?: Record<string, unknown> | null;
  app_name?: string | null;
  app_logo?: string | null;
  package_versions?: PackageVersion[] | Record<string, string> | null;
  show_package_versions?: boolean;
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
      :action-placement="action_placement"
      :ui-layout="ui_layout"
      :app-name="app_name"
      :app-logo="app_logo"
      :package-versions="package_versions"
      :show-package-versions="show_package_versions"
      :preview-mode="preview_mode"
      @submit="handleSubmit"
      @cancel="handleCancel"
    />
  </PublicLayout>
</template>
