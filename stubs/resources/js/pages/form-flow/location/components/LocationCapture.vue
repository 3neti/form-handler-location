<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import LocationEvidenceController from "@/actions/LBHurtado/FormHandlerLocation/Http/Controllers/LocationEvidenceController";
import { useBrowserLocation } from "../composables/useBrowserLocation";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Loader2, AlertCircle, MapPin } from "lucide-vue-next";
import GeoPermissionAlert from "./GeoPermissionAlert.vue";
import FormFlowActions from "@/pages/form-flow/core/components/FormFlowActions.vue";
import FormFlowScreen from "@/pages/form-flow/core/components/FormFlowScreen.vue";
import type { FormFlowUiVariant } from "@/pages/form-flow/core/components/formFlowUiVariant";

export interface LocationCaptureConfig {
  map_provider?: "mapbox" | "google";
  capture_snapshot?: boolean;
  require_address?: boolean;
}

export interface LocationData {
  latitude: number;
  longitude: number;
  timestamp: string;
  accuracy?: number;
  address?: {
    formatted?: string | null;
    city?: string | null;
    state?: string | null;
    country?: string | null;
  } | null;
  map?: string;
}

interface PackageVersion {
  name: string;
  version: string;
}

interface Props {
  flowId: string;
  config?: LocationCaptureConfig;
  modelValue?: LocationData | null;
  uiVariant?: FormFlowUiVariant | string | null;
  actionPlacement?: "inline" | "bottom" | "bottom_sticky" | "viewport_bottom" | string | null;
  uiLayout?: Record<string, unknown> | null;
  appName?: string | null;
  appLogo?: string | null;
  packageVersions?: PackageVersion[] | Record<string, string> | null;
  showPackageVersions?: boolean;
  previewMode?: boolean;
}

interface Emits {
  (e: "update:modelValue", value: LocationData | null): void;
  (e: "submit", value: LocationData): void;
  (e: "cancel"): void;
}

const props = withDefaults(defineProps<Props>(), {
  config: () => ({}),
  modelValue: null,
  uiVariant: "default",
  actionPlacement: null,
  uiLayout: null,
  appName: null,
  appLogo: null,
  packageVersions: null,
  showPackageVersions: false,
  previewMode: false,
});

const emit = defineEmits<Emits>();

// Reactive config values
const requireAddress = computed(() => props.config?.require_address ?? false);
const isImmersive = computed(() => props.uiVariant === "immersive");
const formClass = computed(() =>
  isImmersive.value ? "flex flex-1 flex-col gap-4" : "space-y-6",
);
const mapImageClass = computed(() =>
  isImmersive.value
    ? "h-[52vh] min-h-80 w-full object-cover"
    : "w-full h-64 object-cover",
);

const {
  location,
  loading: geoLoading,
  error: geoError,
  getLocation,
} = useBrowserLocation(3 * 60 * 1000);

const geoAlertRef = ref<InstanceType<typeof GeoPermissionAlert> | null>(null);
const apiError = ref<string | null>(null);
const coordinatesCopied = ref(false);

const parsedLocation = computed(() => {
  return location.value;
});

const formattedAddress = computed(() => {
  return parsedLocation.value?.address?.formatted || "";
});

const mapImage = computed(() => location.value?.map || "");

async function fetchLocation() {
  if (props.previewMode) return;
  const data = await getLocation(false);

  if (geoError.value === "PERMISSION_DENIED") {
    geoAlertRef.value?.open();
    return;
  }

  if (!data) {
    apiError.value = "Failed to get location. Please try again.";
  } else {
    await enrichLocation(data);
  }
}

async function enrichLocation(data: LocationData): Promise<void> {
  apiError.value = null;

  try {
    const route = LocationEvidenceController({ flowId: props.flowId });
    const response = await fetch(route.url, {
      method: route.method.toUpperCase(),
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        ...csrfHeader(),
      },
      body: JSON.stringify({
        latitude: data.latitude,
        longitude: data.longitude,
      }),
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok || !payload?.data) {
      throw new Error(
        typeof payload?.message === "string"
          ? payload.message
          : "Location evidence is temporarily unavailable. Please retry.",
      );
    }

    const enriched: LocationData = {
      ...data,
      address: payload.data.address ?? null,
      map: typeof payload.data.map === "string" ? payload.data.map : undefined,
    };

    if (requireAddress.value && !enriched.address?.formatted) {
      throw new Error("No address could be resolved for this location.");
    }

    location.value = enriched;
    emit("update:modelValue", enriched);
  } catch (error) {
    apiError.value =
      error instanceof Error
        ? error.message
        : "Location evidence is temporarily unavailable. Please retry.";
  }
}

function copyCoordinates() {
  if (!location.value) return;

  const coords = `${location.value.latitude.toFixed(6)}, ${location.value.longitude.toFixed(6)}`;
  navigator.clipboard.writeText(coords).then(() => {
    coordinatesCopied.value = true;
    setTimeout(() => {
      coordinatesCopied.value = false;
    }, 2000);
  });
}

function handleSubmit() {
  if (props.previewMode) return;
  if (!location.value) {
    apiError.value = "Please capture your location first.";
    return;
  }

  if (requireAddress.value && !location.value.address?.formatted) {
    apiError.value =
      "Address information is required but could not be determined.";
    return;
  }

  apiError.value = null;

  emit("submit", location.value);
}

function csrfHeader(): Record<string, string> {
  const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

  return token ? { "X-CSRF-TOKEN": token } : {};
}

function handleCancel() {
  if (props.previewMode) return;
  emit("cancel");
}

// Initialize from modelValue if provided
onMounted(() => {
  if (props.modelValue) {
    location.value = props.modelValue;
  }
});
</script>

<template>
  <FormFlowScreen
    title="Location Required"
    description="Please share your current location to continue"
    :variant="uiVariant"
    :app-name="appName"
    :app-logo="appLogo"
    :package-versions="packageVersions"
    :show-package-versions="showPackageVersions"
    version-context="location"
  >
    <template #icon>
      <MapPin class="h-5 w-5" />
    </template>

    <template #alert>
      <Alert v-if="apiError" variant="destructive" class="mb-6">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>
          {{ apiError }}
        </AlertDescription>
      </Alert>
    </template>

    <form @submit.prevent="handleSubmit" :class="formClass">
      <!-- Get Location Button (shown when no location captured) -->
      <div
        v-if="!parsedLocation"
        class="flex flex-col items-center justify-center py-8 space-y-4"
      >
        <MapPin class="h-12 w-12 text-muted-foreground" />
        <p class="text-sm text-muted-foreground text-center">
          We need to capture your current location to continue
        </p>
        <Button
          type="button"
          @click="fetchLocation"
          :disabled="geoLoading"
          size="lg"
        >
          <Loader2 v-if="geoLoading" class="h-4 w-4 animate-spin mr-2" />
          {{ geoLoading ? "Getting Location..." : "Capture My Location" }}
        </Button>
      </div>

      <!-- Static Map & Location Info (when location captured) -->
      <div v-if="parsedLocation" class="flex min-h-0 flex-1 flex-col gap-4">
        <!-- Map Image -->
        <div v-if="mapImage" class="rounded-lg border overflow-hidden">
          <img
            :src="mapImage"
            alt="Map showing your location"
            :class="mapImageClass"
            loading="lazy"
          />
        </div>

        <!-- Location Address & Coordinates -->
        <div class="space-y-3">
          <div v-if="formattedAddress" class="text-sm font-medium">
            {{ formattedAddress }}
          </div>

          <!-- Copyable Coordinates -->
          <button
            type="button"
            @click="copyCoordinates"
            class="flex items-center gap-2 text-xs text-muted-foreground hover:text-foreground transition-colors"
          >
            <span
              >{{ parsedLocation.latitude.toFixed(6) }},
              {{ parsedLocation.longitude.toFixed(6) }}</span
            >
            <svg
              v-if="!coordinatesCopied"
              class="h-3 w-3"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
              />
            </svg>
            <svg
              v-else
              class="h-3 w-3 text-green-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </button>
        </div>
      </div>

      <FormFlowActions
        :variant="uiVariant"
        :action-placement="actionPlacement"
        :primary-disabled="!location || geoLoading"
        :secondary-disabled="geoLoading"
        @secondary="handleCancel"
      />
    </form>

    <!-- Geo Permission Alert Modal -->
    <GeoPermissionAlert ref="geoAlertRef" />
  </FormFlowScreen>
</template>
