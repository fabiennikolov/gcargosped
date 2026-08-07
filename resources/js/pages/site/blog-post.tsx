import { Link } from '@inertiajs/react';
import PostCard from '@/components/site/post-card';
import SiteLayout from '@/layouts/site-layout';
import type { PageMeta, PostCard as PostCardData, PostDetail } from '@/types/site';

interface Props {
    post: PostDetail;
    related: { data: PostCardData[] } | PostCardData[];
    meta: PageMeta;
}

const unwrap = <T,>(value: { data: T[] } | T[]): T[] => (Array.isArray(value) ? value : value.data);

export default function BlogPost({ post, related, meta }: Props) {
    const others = unwrap(related);

    return (
        <SiteLayout meta={meta}>
            <section className="about-hero">
                <div className="wrap">
                    <Link className="sv-back" href="/blog">
                        ← Всички публикации
                    </Link>
                    {post.category && <span className="eyebrow">{post.category}</span>}
                    <h1>{post.title}</h1>
                    <p className="meta">
                        {post.publishedAt} · {post.readMinutes} мин четене
                    </p>
                </div>
            </section>

            <section>
                <div className="wrap service-body">
                    <article className="glass service-rich">
                        {post.body
                            ?.split(/\n{2,}/)
                            .filter(Boolean)
                            .map((paragraph, index) => <p key={index}>{paragraph.trim()}</p>)}
                    </article>
                </div>
            </section>

            {others.length > 0 && (
                <section>
                    <div className="wrap">
                        <div className="section-head reveal">
                            <h2>Още от блога</h2>
                        </div>
                        <div className="posts">
                            {others.map((other) => (
                                <PostCard key={other.slug} post={other} />
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </SiteLayout>
    );
}
