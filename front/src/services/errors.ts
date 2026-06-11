/** Error body returned by the API (API Platform / Hydra problem details). */
interface ApiErrorBody {
  detail?: string;
  'hydra:description'?: string;
}

/** Structural shape shared by axios errors and the mocked rejections used in tests. */
interface HttpErrorLike {
  isAxiosError?: boolean;
  response?: { status?: number; data?: ApiErrorBody };
}

const asHttpError = (err: unknown): HttpErrorLike | null =>
  typeof err === 'object' && err !== null ? (err as HttpErrorLike) : null;

const responseDetail = (data: ApiErrorBody | undefined): string | undefined =>
  data?.detail || data?.['hydra:description'] || undefined;

/**
 * Extracts a human-readable message from an unknown error thrown by an API call,
 * falling back to the provided message when none is found.
 */
export const extractErrorMessage = (err: unknown, fallback: string): string => {
  const httpErr = asHttpError(err);
  if (httpErr?.response) {
    return responseDetail(httpErr.response.data) || fallback;
  }
  if (httpErr?.isAxiosError) {
    // Axios error without a response (network failure, timeout).
    return fallback;
  }
  if (err instanceof Error && err.message) {
    return err.message;
  }
  return fallback;
};

/** Extracts the HTTP status code from an unknown error, when present. */
export const extractErrorStatus = (err: unknown): number | undefined =>
  asHttpError(err)?.response?.status;
