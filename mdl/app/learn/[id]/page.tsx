export default async function CoursePage({ params }: { params: Promise<{ id: string }> }) {
    const { id } = await params;
    return (
        <div className="container mx-auto px-4 py-8">
            <h1 className="text-3xl font-bold mb-4">Course Detail: {id}</h1>
            <p>Course content for ID {id} coming soon.</p>
        </div>
    );
}
