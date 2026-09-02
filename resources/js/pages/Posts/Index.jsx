import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import dayjs from 'dayjs';
import { create, destroy, edit, show } from '@/routes/posts';

function isEditable(status) {
    return status === 'draft' || status === 'scheduled';
}

function statusClasses(status) {
    switch (status) {
        case 'published':
            return 'bg-green-100 text-green-700';
        case 'scheduled':
            return 'bg-blue-100 text-blue-700';
        case 'draft':
            return 'bg-gray-100 text-gray-700';
        case 'processing':
            return 'bg-yellow-100 text-yellow-700';
        case 'failed':
        case 'partially_failed':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}

function formatStatus(status) {
    return status.replace(/_/g, ' ');
}

function formatScheduledAt(scheduledAt) {
    if (!scheduledAt) {
        return 'Unscheduled';
    }

    return dayjs(scheduledAt).format('MMM D, YYYY h:mm A');
}

function targetNames(post) {
    return (post.targets || [])
        .map((t) => t.social_account?.name)
        .filter(Boolean)
        .join(', ');
}

export default function Index({ posts }) {
    const handleDelete = (post) => {
        if (!confirm('Delete this post?')) {
            return;
        }

        router.delete(destroy.url(post.id));
    };

    return (
        <>
            <Head title="Posts" />
            <div className='p-4'>
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">Posts</h1>
                    <Link
                        href={create.url()}
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition"
                    >
                        + Create Post
                    </Link>
                </div>

                <div className="bg-white shadow rounded-lg overflow-hidden">
                    {posts.length === 0 ? (
                        <div className="p-6 text-center text-gray-500">
                            No posts yet. Create your first scheduled post.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                        >
                                            Content
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap"
                                        >
                                            Scheduled
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap"
                                        >
                                            Status
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {posts.map((post) => (
                                        <tr key={post.id} className="hover:bg-gray-50 align-top">
                                            <td className="px-6 py-4 max-w-md">
                                                <p className="text-sm text-gray-900 whitespace-pre-wrap wrap-break-word">
                                                    {post.content || '(Media only)'}
                                                </p>
                                                {(post.media || []).length > 0 && (
                                                    <p className="mt-1 text-xs text-gray-400">
                                                        {post.media.length}{' '}
                                                        {post.media.length === 1 ? 'attachment' : 'attachments'}
                                                    </p>
                                                )}
                                                {targetNames(post) && (
                                                    <p className="mt-1 text-xs text-gray-400">
                                                        {targetNames(post)}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                                {formatScheduledAt(post.scheduled_at)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    className={`inline-flex px-3 py-1 text-xs rounded-full font-semibold capitalize ${statusClasses(post.status)}`}
                                                >
                                                    {formatStatus(post.status)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-right whitespace-nowrap">
                                                <div className="inline-flex items-center gap-3">
                                                    <Link
                                                        href={show.url(post.id)}
                                                        className="text-sm text-blue-600 hover:underline"
                                                    >
                                                        View
                                                    </Link>
                                                    {isEditable(post.status) && (
                                                        <>
                                                            <Link
                                                                href={edit.url(post.id)}
                                                                className="text-sm text-gray-700 hover:underline"
                                                            >
                                                                Edit
                                                            </Link>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDelete(post)}
                                                                className="text-sm text-red-600 hover:underline"
                                                            >
                                                                Delete
                                                            </button>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
