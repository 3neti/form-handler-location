import { ref } from 'vue';

/**
 * LocationData DTO
 */
export interface LocationData {
    latitude: number;
    longitude: number;
    timestamp: string;
    accuracy?: number;
    address?: GeocodedAddressData | null;
    map?: string; // Optional base64 data URL of the captured map image
}

/**
 * GeocodedAddressData DTO
 */
export interface GeocodedAddressData {
    formatted?: string | null;
    city?: string | null;
    state?: string | null;
    country?: string | null;
}

/**
 * Composable to fetch location from the browser (with freshness, session cache, and force refresh)
 * @param maxAgeMs Optional max cache age in milliseconds (default: 5 minutes)
 */
export function useBrowserLocation(maxAgeMs = 5 * 60 * 1000) {
    const location = ref<LocationData | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const getLocation = async (forceRefresh: boolean = false): Promise<LocationData | null> => {
        // ✅ Skip cache if forcing refresh
        if (!forceRefresh) {
            // 1. Use in-memory cache
            if (location.value) return location.value;

            // 2. Use sessionStorage cache if fresh
            const cached = sessionStorage.getItem('location');
            if (cached) {
                const parsed: LocationData = JSON.parse(cached);
                const age = Date.now() - new Date(parsed.timestamp).getTime();

                if (age <= maxAgeMs) {
                    location.value = parsed;
                    return parsed;
                } else {
                    sessionStorage.removeItem('location'); // Expired
                }
            }
        }

        // ✅ Use browser geolocation
        if (!navigator.geolocation) {
            error.value = 'Geolocation is not supported by this browser.';
            console.warn(error.value);
            return null;
        }

        loading.value = true;
        error.value = null;

        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const { latitude, longitude, accuracy } = position.coords;
                    const timestamp = new Date(position.timestamp).toISOString();
                    const finalLocation: LocationData = {
                        latitude,
                        longitude,
                        timestamp,
                        accuracy,
                        address: null,
                    };

                    location.value = finalLocation;
                    sessionStorage.setItem('location', JSON.stringify(finalLocation));

                    loading.value = false;
                    resolve(finalLocation);
                },
                (geoError) => {
                    if (geoError.code === 1) {
                        error.value = 'PERMISSION_DENIED';
                    } else {
                        error.value = `Error getting location: ${geoError.message}`;
                    }

                    console.error(error.value);
                    loading.value = false;
                    resolve(null);
                }
            );
        });
    };

    const clearCachedLocation = () => {
        sessionStorage.removeItem('location');
        location.value = null;
    };

    return {
        location,
        loading,
        error,
        getLocation,
        clearCachedLocation,
    };
}
