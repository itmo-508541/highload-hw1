import http from 'k6/http';

// https://grafana.com/docs/k6/latest/using-k6/scenarios/advanced-examples/

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

function randomIntFromInterval(min, max) { // min and max included
    return Math.floor(Math.random() * (max - min + 1) + min);
}

export const options = {
    discardResponseBodies: true,
    scenarios: {
        api_items_create: {
            executor: 'constant-vus',
            exec: 'api_items_create',
            vus: 10,
            duration: '100s',
        },

        api_items_read: {
            executor: 'ramping-arrival-rate',
            exec: 'api_items_read',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: 50,
            stages: [
                { target: 25, duration: '1m' },
                { target: 50, duration: '120m' },
            ],
        },

        api_items_update: {
            executor: 'ramping-arrival-rate',
            exec: 'api_items_update',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: 50,
            stages: [
                { target: 25, duration: '1m' },
                { target: 50, duration: '120m' },
            ],
        },
    },
};

export function api_items_create() {
    const value = randomIntFromInterval(1, 10000);

    const response = http.post(`${BASE_URL}/items`, JSON.stringify({ value }), {
        headers: { 'Content-Type': 'application/json' }
    });
}

export function api_items_read() {
    const value = randomIntFromInterval(1, 10000);

    const response = http.get(`${BASE_URL}/items/${value}`);
}

export function api_items_update() {
    const value1 = randomIntFromInterval(1, 10000);
    const value2 = randomIntFromInterval(1, 10000);

    const response = http.put(`${BASE_URL}/items/${value1}`, JSON.stringify({ "value" : value2 }), {
        headers: { 'Content-Type': 'application/json' }
    });
}
