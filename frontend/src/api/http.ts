type RequestOptions = {
  method?: string
  headers?: Record<string, string>
  body?: BodyInit | null
}

export type ApiResponse<T> = {
  code: number
  msg: string
  data?: T
}

const defaultHeaders = {
  Accept: 'application/json',
}

export const request = async <T>(url: string, options: RequestOptions = {}) => {
  const response = await fetch(url, {
    method: options.method ?? 'GET',
    headers: {
      ...defaultHeaders,
      ...options.headers,
    },
    credentials: 'include',
    body: options.body ?? null,
  })

  const result = (await response.json()) as ApiResponse<T>

  return {
    ok: response.ok,
    status: response.status,
    result,
  }
}

export const requestForm = <T>(url: string, body: URLSearchParams, method = 'POST') => {
  return request<T>(url, {
    method,
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
    },
    body: body.toString(),
  })
}
