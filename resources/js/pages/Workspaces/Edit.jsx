import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { index, update } from '@/routes/workspaces';

export default function Edit({ workspace }) {
    const { data, setData, put, processing, errors } = useForm({
        name: workspace.name,
        slug: workspace.slug,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(update.url(workspace.id));
    };

    return (
        <>
            <Head title="Edit Workspace" />
            <div className="max-w-xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
                <Link href={index.url()} className="text-sm text-blue-600 hover:underline">
                    ← Back to Workspaces
                </Link>
                <h1 className="text-2xl font-bold text-gray-900 mt-2 mb-6">Edit Workspace</h1>

                <form onSubmit={handleSubmit} className="bg-white shadow rounded-lg p-6 space-y-4">
                    <div>
                        <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                            Name
                        </label>
                        <input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
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

                    <button
                        type="submit"
                        disabled={processing}
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition disabled:opacity-50"
                    >
                        {processing ? 'Saving...' : 'Save Changes'}
                    </button>
                </form>
            </div>
        </>
    );
}
