import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { update } from '@/routes/posts';
import PostForm from './PostForm';

function toDateTimeLocal(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const pad = (n) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export default function Edit({ post, socialAccounts }) {
    const { data, setData, post: submitPost, processing, errors } = useForm({
        social_account_ids: post.targets?.map((target) => target.social_account_id) ?? [],
        content: post.content ?? '',
        media: [],
        existing_media: post.media ?? [],
        scheduled_at: toDateTimeLocal(post.scheduled_at),
        _method: 'put',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        submitPost(update.url(post.id), { forceFormData: true });
    };

    const handleRemoveExistingMedia = (url) => {
        setData(
            'existing_media',
            data.existing_media.filter((item) => item !== url),
        );
    };

    return (
        <>
            <Head title="Edit Post" />
            <PostForm
                socialAccounts={socialAccounts}
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={handleSubmit}
                submitLabel="Update Post"
                processingLabel="Updating..."
                existingMedia={data.existing_media}
                onRemoveExistingMedia={handleRemoveExistingMedia}
            />
        </>
    );
}
