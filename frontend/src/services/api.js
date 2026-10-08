import axios from 'axios';

const api = axios.create({ baseURL: '/api', withCredentials: true, headers: { 'Content-Type': 'application/json' } });
let csrfToken = null;
let bootstrap = null;
const refreshToken = () => {
  if (!bootstrap) bootstrap = axios.get('/api/auth/check.php', { withCredentials: true }).then(({ data }) => {
    csrfToken = data.data.csrf_token;
    return csrfToken;
  }).finally(() => { bootstrap = null; });
  return bootstrap;
};
api.interceptors.request.use(async (config) => {
  if (!['get', 'head', 'options'].includes(config.method)) {
    if (!csrfToken) await refreshToken();
    config.headers['X-CSRF-Token'] = csrfToken;
  }
  return config;
});
api.interceptors.response.use((response) => {
  if (response.data?.data?.csrf_token) csrfToken = response.data.data.csrf_token;
  return response.data;
}, async (error) => {
  if (error.response?.status === 403 && error.response.data?.message?.includes('session token') && !error.config._csrfRetried) {
    await refreshToken();
    error.config._csrfRetried = true;
    return api.request(error.config);
  }
  if (error.response?.status === 401) window.dispatchEvent(new Event('pathyatra:session-expired'));
  const result = new Error(error.response?.data?.message || error.message || 'The request could not be completed.');
  result.status = error.response?.status;
  return Promise.reject(result);
});
export default api;
