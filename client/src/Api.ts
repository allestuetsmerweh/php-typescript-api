import fetch from 'unfetch';
import {ApiError, ErrorsByField} from './ApiError';

export abstract class Api<
    Endpoints extends string,
    Requests extends {[key in Endpoints]: any},
    Responses extends {[key in Endpoints]: any},
> {
    public abstract baseUrl: string;

    protected fetchFunction: typeof fetch = fetch;

    public call<T extends Endpoints>(
        endpoint: T,
        request: Requests[T],
    ): Promise<Responses[T]> {
        const endpointUrl = `${this.baseUrl}/${endpoint}`;
        const fetchFunction = this.fetchFunction;
        return fetchFunction(endpointUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(request),
        })
            .then(async (response) => {
                const responseText = await response.text();
                if (!response.ok) {
                    const error = this.getApiErrorFromResponseText(responseText);
                    if (error) {
                        throw error;
                    }
                    throw new ApiError(
                        response.status,
                        'Ein Fehler ist aufgetreten. Bitte später nochmals versuchen.',
                    );
                }
                return response.json() as Responses[T];
            });
    }

    public getApiErrorFromResponseText(
        responseText?: string,
    ): ApiError | undefined {
        if (!responseText) {
            return undefined;
        }
        let error;
        try {
            error = JSON.parse(responseText);
        } catch (_e: unknown) {
            return undefined;
        }
        if (typeof error !== 'object' || error === null) {
            return undefined;
        }
        if (
            'status' in error
            && 'message' in error
            && Number.isInteger(error.status)
            && typeof error.message === 'string'
        ) {
            const status = Number(error.status);
            let errorsByField: ErrorsByField | undefined = undefined;
            if (
                'error' in error
                && typeof error.error === 'object'
            ) {
                errorsByField = error.error as unknown as ErrorsByField;
            }
            return new ApiError(status, error.message, errorsByField);
        }
        return undefined;
    }
}
