import AsyncStorage from '@react-native-async-storage/async-storage';

import { normalizeBaseUrl } from '../utils/format';

const TOKEN_KEY = 'public_profile_member_id';
const BASE_URL_KEY = 'public_profile_base_url';

const envBaseUrl = normalizeBaseUrl(process.env.EXPO_PUBLIC_API_BASE_URL ?? 'beforward.lk');

export async function getSession() {
  const [token, savedBaseUrl] = await Promise.all([
    AsyncStorage.getItem(TOKEN_KEY),
    AsyncStorage.getItem(BASE_URL_KEY)
  ]);

  return {
    token,
    baseUrl: normalizeBaseUrl(savedBaseUrl || envBaseUrl)
  };
}

export async function saveBaseUrl(baseUrl: string) {
  await AsyncStorage.setItem(BASE_URL_KEY, normalizeBaseUrl(baseUrl));
}

export async function saveSession(baseUrl: string, token: string) {
  await Promise.all([
    AsyncStorage.setItem(BASE_URL_KEY, normalizeBaseUrl(baseUrl)),
    AsyncStorage.setItem(TOKEN_KEY, token)
  ]);
}

export async function clearSession() {
  await AsyncStorage.removeItem(TOKEN_KEY);
}
