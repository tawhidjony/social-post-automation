import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { store } from '@/routes/posts';
import PostForm from './PostForm';

export default function Create({ socialAccounts }) {
    const { data, setData, post, processing, errors } = useForm({
        social_account_ids: [],
        content: '',
        media: [],
        scheduled_at: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(store.url(), { forceFormData: true });
    };

    return (
        <>
            <Head title="Create & Schedule Post" />
            <PostForm
                socialAccounts={socialAccounts}
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={handleSubmit}
                submitLabel="Schedule Post"
                processingLabel="Scheduling..."
            />
        </>
    );
}
