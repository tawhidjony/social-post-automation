import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { index, store } from '@/routes/workspaces';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        switch: true,
    });

    const handleNameChange = (value) => {
        const nextSlug =
            data.slug === '' || data.slug === slugify(data.name)
                ? slugify(value)
                : data.slug;

        setData({
            ...data,
            name: value,
            slug: nextSlug,
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(store.url());
    };

    return (
        <>
            <Head title="Create Workspace" />
            <div className="max-w-xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
                <Link href={index.url()} className="text-sm text-blue-600 hover:underline">
                    ← Back to Workspaces
                </Link>
                <h1 className="text-2xl font-bold text-gray-900 mt-2 mb-6">Create Workspace</h1>

                <form onSubmit={handleSubmit} className="bg-white shadow rounded-lg p-6 space-y-4">
                    <div>
                        <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                            Name
                        </label>
                        <input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => handleNameChange(e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                    </div>

                    <div>
                        <label htmlFor="slug" className="block text-sm font-medium text-gray-700">
                            Slug
                        </label>
                        <input
                            id="slug"
                            type="text"
                            value={data.slug}
                            onChange={(e) => setData('slug', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        {errors.slug && <p className="mt-1 text-sm text-red-600">{errors.slug}</p>}
                    </div>

                    <label className="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            checked={data.switch}
                            onChange={(e) => setData('switch', e.target.checked)}
                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        Switch to this workspace after creating
                    </label>

                    <button
                        type="submit"
                        disabled={processing}
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition disabled:opacity-50"
                    >
                        {processing ? 'Creating...' : 'Create Workspace'}
                    </button>
                </form>
            </div>
        </>
    );
}

function slugify(value) {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}
