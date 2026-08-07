import { Link } from '@inertiajs/react';
import PostCard from '@/components/site/post-card';
import SiteLayout from '@/layouts/site-layout';
import type { PageMeta, Paginated, PostCard as PostCardData } from '@/types/site';

interface Props {
    posts: Paginated<PostCardData>;
    meta: PageMeta;
}

export default function Blog({ posts, meta }: Props) {
    const pageLinks = posts.links ?? [];

    return (
        <SiteLayout meta={meta}>
            <section className="about-hero">
                <div className="wrap">
                    <span className="eyebrow">Блог</span>
                    <h1>Полезно за транспорта</h1>
                    <p className="lead">Съвети, добри практики и новини от бранша.</p>
                </div>
            </section>

            <section>
                <div className="wrap">
                    {posts.data.length === 0 ? (
                        <p className="blog-note">Все още няма публикации.</p>
                    ) : (
                        <div className="posts">
                            {posts.data.map((post) => (
                                <PostCard key={post.slug} post={post} />
                            ))}
                        </div>
                    )}

                    {pageLinks.length > 3 && (
                        <nav className="pager" aria-label="Страници">
                            {pageLinks.map((link) =>
                                link.url ? (
                                    <Link
                                        key={link.label}
                                        href={link.url}
                                        className={`btn ${link.active ? 'btn-accent' : 'btn-glass'}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span
                                        key={link.label}
                                        className="btn btn-glass is-disabled"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ),
                            )}
                        </nav>
                    )}
                </div>
            </section>
        </SiteLayout>
    );
}
