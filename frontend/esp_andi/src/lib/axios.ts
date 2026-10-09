import axios from 'axios';


export const API_BASE_URL = (
  process.env.NEXT_PUBLIC_LARAVEL_API_URL || 'http://192.168.6.246:8000/api'
).replace(/\/+$/, '');

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 4000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});


apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.code === 'ECONNABORTED') {
      console.warn(`[Axios] Request timeout ke backend: ${error.config?.url}`);
    } else if (error.response) {
      console.warn(
        `[Axios] Backend merespons error ${error.response.status}:`,
        error.response.data
      );
    } else {
      console.warn(`[Axios] Tidak dapat menghubungi server Laravel (${API_BASE_URL}):`, error.message);
    }
    return Promise.reject(error);
  }
);

export default apiClient;
